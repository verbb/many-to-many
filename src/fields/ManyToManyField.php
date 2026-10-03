<?php
namespace verbb\manytomany\fields;

use verbb\manytomany\ManyToMany;
use verbb\manytomany\sources\RelationSourceInterface;
use verbb\manytomany\sources\RelationSources;

use Craft;
use craft\base\EagerLoadingFieldInterface;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\PreviewableFieldInterface;
use craft\elements\ElementCollection;
use craft\elements\Entry;
use craft\fields\BaseRelationField;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\Gql as GqlHelper;
use craft\helpers\Html;
use craft\helpers\StringHelper;
use craft\models\Section;
use craft\services\Gql as GqlService;

use yii\db\Expression;
use yii\db\Schema;

use WeakMap;

use GraphQL\Type\Definition\Type;

class ManyToManyField extends Field implements EagerLoadingFieldInterface, PreviewableFieldInterface
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('manytomany', 'Many to Many');
    }

    public static function icon(): string
    {
        return '@verbb/manytomany/icon-mask.svg';
    }

    public static function dbType(): array|string|null
    {
        return Schema::TYPE_JSON;
    }

    public static function defaultSelectionLabel(): string
    {
        return Craft::t('app', 'Add an entry');
    }

    public static function valueType(): string
    {
        return sprintf('%s[]', ElementInterface::class);
    }

    public static function queryCondition(
        array $instances,
        mixed $value,
        array &$params,
    ): array|string|Expression|false|null {
        if (!is_array($value)) {
            $value = [$value];
        }

        // Support :empty: / :notempty: against the inverse relations table
        if (!isset($value[0]) || !in_array($value[0], [':notempty:', ':empty:', 'not :empty:'], true)) {
            return false;
        }

        $emptyCondition = array_shift($value);
        $existsConditions = [];

        foreach ($instances as $field) {
            /** @var self $field */
            $exists = static::existsQueryCondition($field);

            if ($exists !== null) {
                $existsConditions[] = $exists;
            }
        }

        if (empty($existsConditions)) {
            return false;
        }

        $exists = count($existsConditions) === 1 ? $existsConditions[0] : array_merge(['or'], $existsConditions);

        if (in_array($emptyCondition, [':notempty:', 'not :empty:'], true)) {
            return $exists;
        }

        return ['not', $exists];
    }


    // Properties
    // =========================================================================

    public array $source = [];
    public string $sourceElementType = Entry::class;
    public array $entryTypes = [];
    public ?string $singleField = null;
    public ?string $selectionLabel = null;
    public ?int $maxRelations = null;
    public string $viewMode = BaseRelationField::VIEW_MODE_LIST;

    private ?WeakMap $_submittedSelections = null;
    private ?WeakMap $_preparedChanges = null;


    // Public Methods
    // =========================================================================

    public function __construct($config = [])
    {
        // Remove unused settings
        unset($config['rawValue']);

        parent::__construct($config);
    }

    public function normalizeValue(mixed $value, ?ElementInterface $element = null): mixed
    {
        // Already a list of elements (eager-loaded or set programmatically)
        if (is_array($value) && !array_key_exists('add', $value) && !array_key_exists('delete', $value)) {
            if (empty($value) || reset($value) instanceof ElementInterface) {
                return array_values($value);
            }
        }

        $sourceValue = $this->source['value'] ?? null;
        $adapter = $this->getSourceAdapter();

        if (!$element || !$sourceValue || !$this->singleField || !$adapter) {
            return [];
        }

        $fieldId = Craft::$app->getFields()->getFieldByUid($this->singleField)?->id;

        if (!$fieldId || !$adapter->sourceExists($sourceValue)) {
            return [];
        }

        // Drafts and revisions retain their own selection in element content. Canonical values continue to
        // reflect the live inverse relations so changes made from either side never leave a stale public value.
        if (($element->getIsDraft() || $element->getIsRevision()) && is_array($value)) {
            return $adapter->findElements($this->_normalizeSourceIds($value), $element, $sourceValue);
        }

        return $adapter->findRelatedElements($element, $sourceValue, $fieldId);
    }

    public function normalizeValueFromRequest(mixed $value, ?ElementInterface $element): mixed
    {
        if (!$element) {
            return $this->normalizeValue($value, $element);
        }

        $valid = is_array($value) || $value === null || $value === '';
        $sourceIdsValid = true;
        $sourceIds = $this->_normalizeSourceIds(is_array($value) ? ($value['add'] ?? []) : [], $sourceIdsValid);
        $sourceValue = $this->source['value'] ?? '';
        $adapter = $this->getSourceAdapter();
        $sourceExists = $adapter !== null && $adapter->sourceExists($sourceValue);
        $resolvedElements = $sourceExists
            ? $adapter->findElements($sourceIds, $element, $sourceValue)
            : [];
        $elements = $this->_viewableElements($resolvedElements);
        $selectionIsValid = $valid &&
            $sourceIdsValid &&
            count($sourceIds) === count($resolvedElements) &&
            count($resolvedElements) === count($elements);

        $selectionElements = [];

        if ($selectionIsValid && $sourceExists) {
            foreach (array_merge($elements, $this->_hiddenExistingElements($element, $adapter, $sourceValue)) as $selectedElement) {
                $selectionElements[$selectedElement->id] = $selectedElement;
            }
        }

        $this->_submittedSelections ??= new WeakMap();
        $this->_submittedSelections[$element] = [
            'ids' => array_keys($selectionElements),
            'valid' => $selectionIsValid,
        ];

        // Hidden existing relations remain server-side for serialization, then presentation filters them again.
        return array_values($selectionElements);
    }

    public function serializeValue(mixed $value, ?ElementInterface $element): mixed
    {
        if ($value instanceof ElementCollection) {
            $value = $value->all();
        }

        if (!is_array($value)) {
            return [];
        }

        return $this->_normalizeSourceIds(array_map(
            fn(mixed $item) => $item instanceof ElementInterface ? $item->id : $item,
            $value,
        ));
    }

    public function beforeElementSave(ElementInterface $element, bool $isNew): bool
    {
        if ($this->_preparedChanges !== null && isset($this->_preparedChanges[$element])) {
            unset($this->_preparedChanges[$element]);
        }

        if (!parent::beforeElementSave($element, $isNew)) {
            return false;
        }

        if ($element->getIsDraft()) {
            $selection = $this->_submittedSelections[$element] ?? null;

            if ($selection !== null && !$this->_selectionIsValid($element, $selection)) {
                return false;
            }

            return true;
        }

        if ($element->getIsRevision()) {
            return true;
        }

        $selection = $this->_submittedSelections[$element] ?? null;

        if ($selection !== null) {
            unset($this->_submittedSelections[$element]);
        }

        // Applying a draft duplicates its field values onto the canonical element. Only consume the stored
        // selection when Craft marked this field as changed on that exact draft.
        if (
            $selection === null &&
            $element->duplicateOf?->getIsDraft() &&
            $element->isFieldDirty($this->handle)
        ) {
            $selection = [
                'ids' => $this->serializeValue($element->getFieldValue($this->handle), $element),
                'valid' => true,
            ];
        }

        if ($selection === null) {
            return true;
        }

        if (!$this->_selectionIsValid($element, $selection)) {
            return false;
        }

        $changes = ManyToMany::$plugin->getService()->prepareRelationshipChanges($this, $element, $selection['ids']);

        if ($changes === null) {
            return false;
        }

        $this->_preparedChanges ??= new WeakMap();
        $this->_preparedChanges[$element] = $changes;

        return true;
    }

    public function validateSource(string $attribute): void
    {
        $sourceValue = $this->source['value'] ?? null;
        $adapter = $this->getSourceAdapter();

        if (
            !$adapter ||
            !$sourceValue ||
            ($this->source['type'] ?? null) !== $adapter->sourceType() ||
            !$adapter->sourceExists($sourceValue)
        ) {
            $this->addError($attribute, Craft::t('manytomany', 'Select a valid linked source.'));
        }
    }

    public function validateAssociatedField(string $attribute): void
    {
        $field = $this->singleField ? Craft::$app->getFields()->getFieldByUid($this->singleField) : null;

        $targetElementType = $field instanceof BaseRelationField ? $field::elementType() : null;

        if (!$field instanceof BaseRelationField || !$targetElementType || !RelationSources::get($targetElementType) || $field->getIsTranslatable(null)) {
            $this->addError($attribute, Craft::t('manytomany', 'Select a non-translatable relation field for a supported element type.'));
        }
    }

    public function validateEntryTypes(string $attribute): void
    {
        if ($this->sourceElementType !== Entry::class || empty($this->entryTypes)) {
            return;
        }

        $sourceValue = $this->source['value'] ?? null;
        $section = $sourceValue ? Craft::$app->getEntries()->getSectionByUid($sourceValue) : null;

        if (!$section) {
            return;
        }

        $sectionEntryTypeUids = array_map(fn($entryType) => $entryType->uid, $section->getEntryTypes());

        if (!empty(array_diff($this->entryTypes, $sectionEntryTypeUids))) {
            $this->addError($attribute, Craft::t('manytomany', 'Select entry types that belong to the linked section.'));
        }
    }

    public function getAllowedEntryTypeIds(Section $section): array
    {
        if (empty($this->entryTypes)) {
            return [];
        }

        $selectedEntryTypes = array_flip($this->entryTypes);
        $entryTypeIds = [];

        foreach ($section->getEntryTypes() as $entryType) {
            if (isset($selectedEntryTypes[$entryType->uid])) {
                $entryTypeIds[] = $entryType->id;
            }
        }

        return $entryTypeIds;
    }

    public function getSourceAdapter(): ?RelationSourceInterface
    {
        return RelationSources::get($this->sourceElementType);
    }

    public function getSettingsHtml(): string
    {
        $entryTypes = [];
        $fields = [];
        $sourceTypes = [];
        $sourceTypeOptions = [];

        foreach (RelationSources::all() as $elementType => $adapter) {
            $sourceTypeOptions[$elementType] = $adapter->label();
            $sourceTypes[$elementType] = [
                'label' => $adapter->label(),
                'sourceType' => $adapter->sourceType(),
                'sourceLabel' => $adapter->sourceLabel(),
                'sourceInstructions' => $adapter->sourceInstructions(),
                'options' => $adapter->sourceOptions(),
                'defaultSelectionLabel' => $adapter->defaultSelectionLabel(),
            ];
        }

        foreach (Craft::$app->getEntries()->getAllSections() as $section) {
            foreach ($section->getEntryTypes() as $entryType) {
                $entryTypes[$entryType->uid] ??= [
                    'name' => $entryType->name,
                    'sectionUids' => [],
                ];
                $entryTypes[$entryType->uid]['sectionUids'][] = $section->uid;
            }
        }

        $entryTypeOptions = [];

        foreach ($entryTypes as $entryTypeUid => $entryType) {
            $entryTypeOptions[] = [
                'label' => $entryType['name'],
                'value' => $entryTypeUid,
                'data' => [
                    'sections' => implode(',', $entryType['sectionUids']),
                ],
            ];
        }

        // The inverse relationship must be stored by a non-translatable relation field that targets a core element type.
        foreach (Craft::$app->getFields()->getAllFields() as $field) {
            $targetElementType = $field instanceof BaseRelationField ? $field::elementType() : null;

            if ($field instanceof BaseRelationField && $targetElementType && RelationSources::get($targetElementType) && !$field->getIsTranslatable(null)) {
                $fields[$field->uid] = sprintf('%s — %s', $field->name, $field::displayName());
            }
        }

        if (empty($this->source)) {
            $this->source = ['type' => 'section', 'value' => ''];
        }

        return Craft::$app->getView()->renderTemplate('manytomany/field/settings', [
            'field' => $this,
            'sourceTypes' => $sourceTypes,
            'sourceTypeOptions' => $sourceTypeOptions,
            'entryTypes' => $entryTypeOptions,
            'fields' => $fields,
            'viewModes' => [
                BaseRelationField::VIEW_MODE_LIST => Craft::t('app', 'List'),
                BaseRelationField::VIEW_MODE_LIST_INLINE => Craft::t('app', 'Inline list'),
                BaseRelationField::VIEW_MODE_CARDS => Craft::t('app', 'Cards'),
                BaseRelationField::VIEW_MODE_CARDS_GRID => Craft::t('app', 'Card grid'),
            ],
        ]);
    }

    public function afterElementSave(ElementInterface $element, bool $isNew): void
    {
        $changes = $this->_preparedChanges[$element] ?? null;

        if ($changes !== null) {
            unset($this->_preparedChanges[$element]);
            ManyToMany::$plugin->getService()->saveRelationship($element, $changes);
        }

        parent::afterElementSave($element, $isNew);
    }

    public function getPreviewHtml($value, ElementInterface $element): string
    {
        return Cp::elementPreviewHtml($this->_viewableElements($value));
    }

    public function getEagerLoadingMap(array $sourceElements): array|null|false
    {
        $sourceValue = $this->source['value'] ?? null;
        $adapter = $this->getSourceAdapter();

        if (!$sourceValue || !$this->singleField || empty($sourceElements) || !$adapter) {
            return false;
        }

        $fieldId = Craft::$app->getFields()->getFieldByUid($this->singleField)?->id;

        if (!$fieldId || !$adapter->sourceExists($sourceValue)) {
            return false;
        }

        return $adapter->getEagerLoadingMap($sourceElements, $sourceValue, $fieldId);
    }

    public function getEagerLoadingGqlConditions(): ?array
    {
        $sourceValue = $this->source['value'] ?? null;
        $adapter = $this->getSourceAdapter();

        if (!$sourceValue || !$adapter || !$adapter->sourceExists($sourceValue)) {
            return null;
        }

        return $adapter->getEagerLoadingGqlConditions($sourceValue);
    }

    public function getContentGqlType(): array
    {
        $adapter = $this->getSourceAdapter() ?? RelationSources::get(Entry::class);
        $interface = $adapter->gqlInterface();
        $arguments = $adapter->gqlArguments();
        $resolver = $adapter->gqlResolver();

        return [
            'name' => $this->handle,
            'type' => Type::nonNull(Type::listOf($interface::getType())),
            'args' => $arguments::getArguments(),
            'resolve' => function($source, $arguments, $context, $resolveInfo) use ($resolver) {
                $target = $source->{$this->handle};

                if (empty($arguments)) {
                    return $target;
                }

                // Convert the already-resolved elements to a query when field arguments need to be applied.
                $arguments['id'] = ArrayHelper::getColumn($target, 'id');
                $arguments['siteId'] = $target[0]->siteId ?? null;

                return $resolver::resolve(null, $arguments, $context, $resolveInfo);
            },
            'complexity' => GqlHelper::relatedArgumentComplexity(GqlService::GRAPHQL_COMPLEXITY_EAGER_LOAD),
        ];
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['sourceElementType'], 'in', 'range' => array_keys(RelationSources::all())];
        $rules[] = [['source'], 'validateSource'];
        $rules[] = [['singleField'], 'validateAssociatedField'];
        $rules[] = [['entryTypes'], 'each', 'rule' => ['string']];
        $rules[] = [['entryTypes'], 'validateEntryTypes'];
        $rules[] = [['maxRelations'], 'number', 'integerOnly' => true, 'min' => 1, 'skipOnEmpty' => true];
        $rules[] = [['viewMode'], 'in', 'range' => [
            BaseRelationField::VIEW_MODE_LIST,
            BaseRelationField::VIEW_MODE_LIST_INLINE,
            BaseRelationField::VIEW_MODE_CARDS,
            BaseRelationField::VIEW_MODE_CARDS_GRID,
        ]];

        return $rules;
    }

    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        $view = Craft::$app->getView();

        // Validate settings
        if (empty($this->source)) {
            return Craft::t('manytomany', 'To use the Many to Many plugin you need to set a source.');
        }

        if (empty($this->singleField)) {
            return Craft::t('manytomany', 'To use the Many to Many plugin you need associate it with a related field.');
        }

        $singleFieldModel = Craft::$app->getFields()->getFieldByUid($this->singleField);

        if ($singleFieldModel && $singleFieldModel->getIsTranslatable(null)) {
            return Craft::t('manytomany', 'The Many to Many plugin does not currently work with localized content.');
        }

        $adapter = $this->getSourceAdapter();
        $sourceValue = $this->source['value'] ?? null;
        $targetElementType = $singleFieldModel instanceof BaseRelationField ? $singleFieldModel::elementType() : null;

        if (!$adapter || !$sourceValue || !$adapter->sourceExists($sourceValue)) {
            return Craft::t('manytomany', 'This relationship field does not have a valid linked source.');
        }

        if (!$singleFieldModel instanceof BaseRelationField || !$targetElementType || !$element instanceof $targetElementType) {
            return Craft::t('manytomany', 'The associated field cannot relate to this element type.');
        }

        // Match Craft relation fields: CP input includes disabled/non-live related entries.
        // Keep posted values as-is so validation failures don't lose the current selection.
        $relatedSection = $this->sourceElementType === Entry::class
            ? Craft::$app->getEntries()->getSectionByUid($sourceValue)
            : null;
        $criteria = $adapter->selectorCriteria($sourceValue);

        if ($relatedSection && !empty($this->entryTypes)) {
            $criteria['typeId'] = $this->getAllowedEntryTypeIds($relatedSection) ?: [0];
        }

        if (
            $this->singleField &&
            !$element->getIsDraft() &&
            !$element->getIsRevision() &&
            !$element->isFieldDirty($this->handle)
        ) {
            $value = $adapter->findRelatedElements($element, $sourceValue, $singleFieldModel->id, true);
        }

        $value = $this->_viewableElements($value);

        return $view->renderTemplate('manytomany/field/input', [
            'name' => $this->handle,
            'value' => $value,
            'id' => Html::id($this->handle),
            'elementType' => $adapter->elementType(),
            'sources' => $adapter->selectorSources($sourceValue),
            'criteria' => empty($criteria) ? null : $criteria,
            'selectionLabel' => $this->selectionLabel ? Craft::t('site', $this->selectionLabel) : $adapter->defaultSelectionLabel(),
            'maxRelations' => $this->maxRelations,
            'viewMode' => $inline ? BaseRelationField::VIEW_MODE_LIST_INLINE : $this->viewMode,
        ]);
    }


    // Private Methods
    // =========================================================================

    private function _selectionIsValid(ElementInterface $element, array $selection): bool
    {
        if (!$selection['valid']) {
            $element->addError($this->handle, Craft::t('manytomany', 'One or more selected elements are invalid.'));
            return false;
        }

        if ($this->maxRelations !== null && count($selection['ids']) > $this->maxRelations) {
            $element->addError($this->handle, Craft::t('manytomany', 'Select no more than {limit, number} {limit, plural, one{element} other{elements}}.', [
                'limit' => $this->maxRelations,
            ]));
            return false;
        }

        return true;
    }

    private function _viewableElements(mixed $value): array
    {
        if ($value instanceof ElementCollection) {
            $value = $value->all();
        }

        if (!is_array($value)) {
            return [];
        }

        $user = Craft::$app->getUser()->getIdentity();

        if (!$user) {
            return [];
        }

        $elements = Craft::$app->getElements();

        return array_values(array_filter(
            $value,
            fn(mixed $element) => $element instanceof ElementInterface && $elements->canView($element, $user),
        ));
    }

    private function _hiddenExistingElements(ElementInterface $element, RelationSourceInterface $adapter, string $sourceValue): array
    {
        if ($element->getIsDraft() || $element->getIsRevision()) {
            $value = $element->getFieldValue($this->handle);
            $value = $value instanceof ElementCollection ? $value->all() : $value;
            $existingElements = is_array($value) ? $value : [];
        } else {
            $fieldId = $this->singleField ? Craft::$app->getFields()->getFieldByUid($this->singleField)?->id : null;
            $existingElements = $fieldId
                ? $adapter->findRelatedElements($element, $sourceValue, $fieldId, true)
                : [];
        }

        $viewableIds = array_flip(array_map(
            fn(ElementInterface $existingElement) => $existingElement->id,
            $this->_viewableElements($existingElements),
        ));

        return array_values(array_filter(
            $existingElements,
            fn(mixed $existingElement) => $existingElement instanceof ElementInterface && !isset($viewableIds[$existingElement->id]),
        ));
    }

    private function _normalizeSourceIds(mixed $values, ?bool &$valid = null): array
    {
        $valid = true;
        $sourceIds = [];

        if ($values === null || $values === '') {
            return [];
        }

        if (!is_array($values)) {
            $values = [$values];
        }

        foreach ($values as $value) {
            $sourceId = filter_var($value, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1],
            ]);

            if ($sourceId === false) {
                $valid = false;
                continue;
            }

            $sourceIds[] = $sourceId;
        }

        return array_values(array_unique($sourceIds));
    }

    /**
     * Condition for elements that have at least one inverse relation via the associated field.
     */
    private static function existsQueryCondition(self $field): ?array
    {
        $sourceValue = $field->source['value'] ?? null;
        $adapter = $field->getSourceAdapter();

        if (!$field->singleField || !$sourceValue || !$adapter || !$adapter->sourceExists($sourceValue)) {
            return null;
        }

        $associatedFieldId = Craft::$app->getFields()->getFieldByUid($field->singleField)?->id;

        if (!$associatedFieldId) {
            return null;
        }

        return $adapter->existsQueryCondition(
            $associatedFieldId,
            $sourceValue,
            sprintf('%s_%s', $field->handle, StringHelper::randomString(5)),
        );
    }

}
