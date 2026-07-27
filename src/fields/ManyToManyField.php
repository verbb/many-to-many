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
use craft\elements\Entry;
use craft\gql\arguments\elements\Entry as EntryArguments;
use craft\gql\interfaces\elements\Entry as EntryInterface;
use craft\gql\resolvers\elements\Entry as EntryResolver;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\Db;
use craft\helpers\Gql as GqlHelper;
use craft\helpers\Html;
use craft\helpers\StringHelper;
use craft\services\Gql as GqlService;

use GraphQL\Type\Definition\Type;

use yii\db\Expression;


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
        return null;
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


    // Public Methods
    // =========================================================================

    public function __construct($config = [])
    {
        // Remove unused settings
        unset($config['rawValue']);

        parent::__construct($config);
    }

    public function normalizeValue(mixed $value, ElementInterface $element = null): mixed
    {
        // Already a list of entries (eager-loaded or set programmatically)
        if (is_array($value) && !array_key_exists('add', $value) && !array_key_exists('delete', $value)) {
            if (empty($value) || reset($value) instanceof Entry) {
                return array_values($value);
            }
        }

        $sourceValue = $this->source['value'] ?? null;
        $isPosted = is_array($value) && (array_key_exists('add', $value) || array_key_exists('delete', $value));

        // Save the raw value for add/delete elements to use in `saveRelationship()`. We have to use the cache
        // as this isn't retained in `afterElementSave()`, and we want to wait until after the element has saved
        // to save the relationship, in case something went wrong with the element saving.
        if ($element?->canonicalUid && ($isPosted || $value === '')) {
            $cacheKey = implode('--', ['many-to-many', $this->handle, $element->canonicalUid]);
            Craft::$app->getCache()->set($cacheKey, $isPosted ? $value : []);
        }

        if (!$element || !$sourceValue || !$this->singleField) {
            return [];
        }

        $relatedSection = Craft::$app->getEntries()->getSectionByUid($sourceValue);

        if (!$relatedSection) {
            return [];
        }

        // Posted values are the current selection in the element select (`add`) plus removals (`delete`).
        // Use `add` for validation / redisplay so required fields and failed saves keep the selection —
        // DB relations aren't updated until afterElementSave().
        if ($isPosted) {
            $addIds = array_values(array_unique(array_filter(array_map('intval', (array)($value['add'] ?? [])))));

            if (empty($addIds)) {
                return [];
            }

            return Entry::find()
                ->id($addIds)
                ->siteId($element->siteId)
                ->status(null)
                ->section($relatedSection)
                ->fixedOrder()
                ->all();
        }

        return ManyToMany::$plugin->getService()->getRelatedEntries($element, $relatedSection, $this->singleField);
    }

    public function getSettingsHtml(): string
    {
        $elements = [];
        $fields = [];

        // Group the Sections into an array
        foreach (Craft::$app->getEntries()->getAllSections() as $section) {
            $elements[$section->uid] = $section->name;
        }

        // Group Field Types into an array
        foreach (Craft::$app->getFields()->getAllFields() as $field) {
            $fields[$field->uid] = $field->name;
        }

        // Get the Section Source
        if (empty($this->source)) {
            $this->source = ['type' => '', 'value' => ''];
        }

        return Craft::$app->getView()->renderTemplate('manytomany/field/settings', [
            'field' => $this,
            'elements' => $elements,
            'fields' => $fields,
        ]);
    }

    public function afterElementSave(ElementInterface $element, bool $isNew): void
    {
        ManyToMany::$plugin->getService()->saveRelationship($this, $element);

        parent::afterElementSave($element, $isNew);
    }

    public function getPreviewHtml($value, ElementInterface $element): string
    {
        return Cp::elementPreviewHtml($value);
    }

    public function getEagerLoadingMap(array $sourceElements): array|null|false
    {
        $sourceValue = $this->source['value'] ?? null;

        if (!$sourceValue || !$this->singleField || empty($sourceElements)) {
            return false;
        }

        $fieldId = Db::idByUid(Table::FIELDS, $this->singleField);

        if (!$fieldId) {
            return false;
        }

        $sourceElementIds = [];

        foreach ($sourceElements as $sourceElement) {
            $sourceElementIds[] = $sourceElement->id;
        }

        $sourceSiteId = $sourceElements[0]->siteId;

        // Relations live on the associated Entries field (source = related entry, target = this element)
        $map = (new Query())
            ->select(['targetId as source', 'sourceId as target'])
            ->from([Table::RELATIONS])
            ->where([
                'and',
                [
                    'fieldId' => $fieldId,
                    'targetId' => $sourceElementIds,
                ],
                [
                    'or',
                    ['sourceSiteId' => $sourceSiteId],
                    ['sourceSiteId' => null],
                ],
            ])
            ->orderBy(['sortOrder' => SORT_ASC])
            ->all();

        $criteria = [
            'status' => null,
            'siteId' => $sourceSiteId,
        ];

        $section = Craft::$app->getEntries()->getSectionByUid($sourceValue);

        if ($section) {
            $criteria['sectionId'] = $section->id;
        }

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
                // Convert the already-resolved entries to an entries query. This is because `normalizeValue`
                // doesn't return the traditional EntryElementQuery value.
                $target = $source->{$this->handle};
                $arguments['id'] = ArrayHelper::getColumn($target, 'id');
                $arguments['siteId'] = $target[0]->siteId ?? null;

                return EntryResolver::resolve(null, $arguments, $context, $resolveInfo);
            },
            'complexity' => GqlHelper::relatedArgumentComplexity(GqlService::GRAPHQL_COMPLEXITY_EAGER_LOAD),
        ];
    }


    // Protected Methods
    // =========================================================================

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

        return $view->renderTemplate('manytomany/field/input', [
            'name' => $this->handle,
            'value' => $value,
            'id' => Html::id($this->handle),
            'section' => $this->source['value'] ?? null,
            'selectionLabel' => $this->selectionLabel ? Craft::t('site', $this->selectionLabel) : static::defaultSelectionLabel(),
        ]);
    }


    // Private Methods
    // =========================================================================

    /**
     * Condition for elements that have at least one inverse relation via the associated field.
     */
    private static function existsQueryCondition(self $field): ?array
    {
        if (!$field->singleField) {
            return null;
        }

        $associatedFieldId = Db::idByUid(Table::FIELDS, $field->singleField);

        if (!$associatedFieldId) {
            return null;
        }

        $ns = sprintf('%s_%s', $field->handle, StringHelper::randomString(5));

        $query = (new Query())
            ->from(["relations_$ns" => Table::RELATIONS])
            ->innerJoin(["elements_$ns" => Table::ELEMENTS], "[[elements_$ns.id]] = [[relations_$ns.sourceId]]")
            ->where([
                'and',
                "[[relations_$ns.targetId]] = [[elements.id]]",
                [
                    "relations_$ns.fieldId" => $associatedFieldId,
                    "elements_$ns.dateDeleted" => null,
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
