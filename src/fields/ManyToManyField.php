<?php
namespace verbb\manytomany\fields;

use verbb\manytomany\ManyToMany;

use Craft;
use craft\base\EagerLoadingFieldInterface;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\PreviewableFieldInterface;
use craft\db\Query;
use craft\db\Table;
use craft\elements\ElementCollection;
use craft\elements\Entry;
use craft\fields\BaseRelationField;
use craft\fields\Entries;
use craft\gql\arguments\elements\Entry as EntryArguments;
use craft\gql\interfaces\elements\Entry as EntryInterface;
use craft\gql\resolvers\elements\Entry as EntryResolver;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\Db;
use craft\helpers\Gql as GqlHelper;
use craft\helpers\Html;
use craft\helpers\StringHelper;
use craft\models\Section;
use craft\services\Gql as GqlService;

use yii\db\Expression;
use yii\db\Schema;

use DateTime;
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
        return sprintf('%s[]', Entry::class);
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
        // Already a list of entries (eager-loaded or set programmatically)
        if (is_array($value) && !array_key_exists('add', $value) && !array_key_exists('delete', $value)) {
            if (empty($value) || reset($value) instanceof Entry) {
                return array_values($value);
            }
        }

        $sourceValue = $this->source['value'] ?? null;

        if (!$element || !$sourceValue || !$this->singleField) {
            return [];
        }

        $relatedSection = Craft::$app->getEntries()->getSectionByUid($sourceValue);

        if (!$relatedSection) {
            return [];
        }

        // Drafts and revisions retain their own selection in element content. Canonical values continue to
        // reflect the live inverse relations so changes made from either side never leave a stale public value.
        if (($element->getIsDraft() || $element->getIsRevision()) && is_array($value)) {
            return $this->_entriesForSourceIds($this->_normalizeSourceIds($value), $element);
        }

        return ManyToMany::$plugin->getService()->getRelatedEntries($element, $relatedSection, $this->singleField);
    }

    public function normalizeValueFromRequest(mixed $value, ?ElementInterface $element): mixed
    {
        if (!$element) {
            return $this->normalizeValue($value, $element);
        }

        $valid = is_array($value) || $value === null || $value === '';
        $sourceIdsValid = true;
        $sourceIds = $this->_normalizeSourceIds(is_array($value) ? ($value['add'] ?? []) : [], $sourceIdsValid);
        $entries = $this->_entriesForSourceIds($sourceIds, $element);
        $resolvedIds = array_map(fn(Entry $entry) => $entry->id, $entries);

        $this->_submittedSelections ??= new WeakMap();
        $this->_submittedSelections[$element] = [
            'ids' => $resolvedIds,
            'valid' => $valid && $sourceIdsValid && count($sourceIds) === count($resolvedIds),
        ];

        return $entries;
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
            fn(mixed $item) => $item instanceof Entry ? $item->id : $item,
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

        if (!$sourceValue || !Craft::$app->getEntries()->getSectionByUid($sourceValue)) {
            $this->addError($attribute, Craft::t('manytomany', 'Select a valid linked section.'));
        }
    }

    public function validateAssociatedField(string $attribute): void
    {
        $field = $this->singleField ? Craft::$app->getFields()->getFieldByUid($this->singleField) : null;

        if (!$field instanceof Entries || $field->getIsTranslatable(null)) {
            $this->addError($attribute, Craft::t('manytomany', 'Select a non-translatable Entries field.'));
        }
    }

    public function getSettingsHtml(): string
    {
        $elements = [];
        $fields = [];

        // Group the Sections into an array
        foreach (Craft::$app->getEntries()->getAllSections() as $section) {
            $elements[$section->uid] = $section->name;
        }

        // Only non-translatable Entries fields can own the inverse relationship.
        foreach (Craft::$app->getFields()->getAllFields() as $field) {
            if ($field instanceof Entries && !$field->getIsTranslatable(null)) {
                $fields[$field->uid] = $field->name;
            }
        }

        // Get the Section Source
        if (empty($this->source)) {
            $this->source = ['type' => '', 'value' => ''];
        }

        return Craft::$app->getView()->renderTemplate('manytomany/field/settings', [
            'field' => $this,
            'elements' => $elements,
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
        if ($value instanceof ElementCollection) {
            $value = $value->all();
        }

        return Cp::elementPreviewHtml($value);
    }

    public function getEagerLoadingMap(array $sourceElements): array|null|false
    {
        $sourceValue = $this->source['value'] ?? null;

        if (!$sourceValue || !$this->singleField || empty($sourceElements)) {
            return false;
        }

        $fieldId = Craft::$app->getFields()->getFieldByUid($this->singleField)?->id;
        $section = Craft::$app->getEntries()->getSectionByUid($sourceValue);

        if (!$fieldId || !$section) {
            return false;
        }

        $sourceElementIds = [];

        foreach ($sourceElements as $sourceElement) {
            $sourceElementIds[] = $sourceElement->id;
        }

        $sourceSiteId = $sourceElements[0]->siteId;

        // Relations live on the associated Entries field (source = related entry, target = this element)
        $mapQuery = (new Query())
            ->select(['relations.targetId as source', 'relations.sourceId as target'])
            ->from(['relations' => Table::RELATIONS])
            ->innerJoin(['relatedElements' => Table::ELEMENTS], '[[relatedElements.id]] = [[relations.sourceId]]')
            ->innerJoin(['relatedEntries' => Table::ENTRIES], '[[relatedEntries.id]] = [[relations.sourceId]]')
            ->where([
                'and',
                [
                    'relations.fieldId' => $fieldId,
                    'relations.targetId' => $sourceElementIds,
                    'relatedElements.draftId' => null,
                    'relatedElements.revisionId' => null,
                ],
                [
                    'or',
                    ['relations.sourceSiteId' => $sourceSiteId],
                    ['relations.sourceSiteId' => null],
                ],
            ]);

        $orderBy = [
            'relatedEntries.postDate' => SORT_DESC,
            'relatedElements.id' => SORT_DESC,
        ];

        if ($section->type === Section::TYPE_STRUCTURE && $section->structureId) {
            $mapQuery->innerJoin(
                ['relatedStructureElements' => Table::STRUCTUREELEMENTS],
                [
                    'and',
                    '[[relatedStructureElements.elementId]] = [[relations.sourceId]]',
                    ['relatedStructureElements.structureId' => $section->structureId],
                ],
            );
            $orderBy = ['relatedStructureElements.lft' => SORT_ASC] + $orderBy;
        }

        $map = $mapQuery
            ->orderBy($orderBy)
            ->all();

        $criteria = [
            'siteId' => $sourceSiteId,
        ];

        $criteria['sectionId'] = $section->id;

        return [
            'elementType' => Entry::class,
            'map' => $map,
            'criteria' => $criteria,
        ];
    }

    public function getEagerLoadingGqlConditions(): ?array
    {
        $sourceUid = $this->source['value'] ?? null;
        $allowedEntities = GqlHelper::extractAllowedEntitiesFromSchema();
        $sectionUids = $allowedEntities['sections'] ?? [];

        if (!$sourceUid || empty($sectionUids) || !in_array($sourceUid, $sectionUids, true)) {
            return null;
        }

        $section = Craft::$app->getEntries()->getSectionByUid($sourceUid);

        if (!$section) {
            return null;
        }

        return [
            'sectionId' => [$section->id],
        ];
    }

    public function getContentGqlType(): array
    {
        return [
            'name' => $this->handle,
            'type' => Type::nonNull(Type::listOf(EntryInterface::getType())),
            'args' => EntryArguments::getArguments(),
            'resolve' => function($source, $arguments, $context, $resolveInfo) {
                $target = $source->{$this->handle};

                if (empty($arguments)) {
                    return $target;
                }

                // Convert the already-resolved entries to an entries query when field arguments need to be applied.
                $arguments['id'] = ArrayHelper::getColumn($target, 'id');
                $arguments['siteId'] = $target[0]->siteId ?? null;

                return EntryResolver::resolve(null, $arguments, $context, $resolveInfo);
            },
            'complexity' => GqlHelper::relatedArgumentComplexity(GqlService::GRAPHQL_COMPLEXITY_EAGER_LOAD),
        ];
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['source'], 'validateSource'];
        $rules[] = [['singleField'], 'validateAssociatedField'];
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

        if ($singleFieldModel && $singleFieldModel->getIsTranslatable($element)) {
            return Craft::t('manytomany', 'The Many to Many plugin does not currently work with localized content.');
        }

        // For this iteration of the plugin, everything is a SECTION, but it's setup, so it can be
        // refactored in the future to allow for multiple types
        if (!($element instanceof Entry)) {
            return Craft::t('manytomany', 'For this version of the Many to Many plugin, you can only use this field with Entries.');
        }

        // Match Craft relation fields: CP input includes disabled/non-live related entries.
        // Keep posted values as-is so validation failures don't lose the current selection.
        $sourceValue = $this->source['value'] ?? null;
        $relatedSection = $sourceValue ? Craft::$app->getEntries()->getSectionByUid($sourceValue) : null;

        if (
            $relatedSection &&
            $this->singleField &&
            !$element->getIsDraft() &&
            !$element->getIsRevision() &&
            !$element->isFieldDirty($this->handle)
        ) {
            $value = ManyToMany::$plugin->getService()->getRelatedEntries($element, $relatedSection, $this->singleField, true);
        }

        return $view->renderTemplate('manytomany/field/input', [
            'name' => $this->handle,
            'value' => $value,
            'id' => Html::id($this->handle),
            'section' => $this->source['value'] ?? null,
            'selectionLabel' => $this->selectionLabel ? Craft::t('site', $this->selectionLabel) : static::defaultSelectionLabel(),
            'maxRelations' => $this->maxRelations,
            'viewMode' => $inline ? BaseRelationField::VIEW_MODE_LIST_INLINE : $this->viewMode,
        ]);
    }


    // Private Methods
    // =========================================================================

    private function _entriesForSourceIds(array $sourceIds, ElementInterface $element): array
    {
        $sourceValue = $this->source['value'] ?? null;
        $relatedSection = $sourceValue ? Craft::$app->getEntries()->getSectionByUid($sourceValue) : null;

        if (!$relatedSection || empty($sourceIds)) {
            return [];
        }

        return Entry::find()
            ->id($sourceIds)
            ->siteId($element->siteId)
            ->status(null)
            ->section($relatedSection)
            ->fixedOrder()
            ->all();
    }

    private function _selectionIsValid(ElementInterface $element, array $selection): bool
    {
        if (!$selection['valid']) {
            $element->addError($this->handle, Craft::t('manytomany', 'One or more selected entries are invalid.'));
            return false;
        }

        if ($this->maxRelations !== null && count($selection['ids']) > $this->maxRelations) {
            $element->addError($this->handle, Craft::t('manytomany', 'Select no more than {limit, number} {limit, plural, one{entry} other{entries}}.', [
                'limit' => $this->maxRelations,
            ]));
            return false;
        }

        return true;
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
        $section = $sourceValue ? Craft::$app->getEntries()->getSectionByUid($sourceValue) : null;

        if (!$field->singleField || !$section) {
            return null;
        }

        $associatedFieldId = Craft::$app->getFields()->getFieldByUid($field->singleField)?->id;

        if (!$associatedFieldId) {
            return null;
        }

        $ns = sprintf('%s_%s', $field->handle, StringHelper::randomString(5));
        $now = new DateTime();
        $now->setTime((int)$now->format('H'), (int)$now->format('i'), 59);
        $currentTimeDb = Db::prepareDateForDb($now);

        $query = (new Query())
            ->from(["relations_$ns" => Table::RELATIONS])
            ->innerJoin(["elements_$ns" => Table::ELEMENTS], "[[elements_$ns.id]] = [[relations_$ns.sourceId]]")
            ->innerJoin(["entries_$ns" => Table::ENTRIES], "[[entries_$ns.id]] = [[elements_$ns.id]]")
            ->innerJoin(
                ["elements_sites_$ns" => Table::ELEMENTS_SITES],
                "[[elements_sites_$ns.elementId]] = [[elements_$ns.id]] AND [[elements_sites_$ns.siteId]] = [[elements_sites.siteId]]",
            )
            ->where([
                'and',
                "[[relations_$ns.targetId]] = [[elements.id]]",
                [
                    "relations_$ns.fieldId" => $associatedFieldId,
                    "elements_$ns.dateDeleted" => null,
                    "elements_$ns.draftId" => null,
                    "elements_$ns.revisionId" => null,
                    "elements_$ns.enabled" => true,
                    "elements_sites_$ns.enabled" => true,
                    "entries_$ns.sectionId" => $section->id,
                ],
                ['<=', "entries_$ns.postDate", $currentTimeDb],
                [
                    'or',
                    ["entries_$ns.expiryDate" => null],
                    ['>', "entries_$ns.expiryDate", $currentTimeDb],
                ],
                [
                    'or',
                    ["relations_$ns.sourceSiteId" => null],
                    ["relations_$ns.sourceSiteId" => new Expression('[[elements_sites.siteId]]')],
                ],
            ]);

        return ['exists', $query];
    }
}
