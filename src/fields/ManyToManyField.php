<?php
namespace verbb\manytomany\fields;

use verbb\manytomany\ManyToMany;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\PreviewableFieldInterface;
use craft\elements\Entry;
use craft\gql\arguments\elements\Entry as EntryArguments;
use craft\gql\interfaces\elements\Entry as EntryInterface;
use craft\gql\resolvers\elements\Entry as EntryResolver;
use craft\helpers\Cp;
use craft\helpers\Gql as GqlHelper;
use craft\services\Gql as GqlService;
use craft\helpers\ArrayHelper;
use craft\helpers\Html;

use GraphQL\Type\Definition\Type;


class ManyToManyField extends Field implements PreviewableFieldInterface
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('manytomany', 'Many to Many');
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


    // Properties
    // =========================================================================

    public array $source = [];
    public ?string $singleField = null;
    public ?string $selectionLabel = null;


    // Public Methods
    // =========================================================================

    public function normalizeValue(mixed $value, ElementInterface $element = null): mixed
    {
        $sourceValue = $this->source['value'] ?? null;

        // Save the raw value for add/delete elements to use in `saveRelationship()`. We have to use the cache
        // as this isn't retained in `afterElementSave()`, and we want to wait until after the element has saved
        // to save the relationship, in case something went wrong with the element saving.
        $cacheKey = implode('--', ['many-to-many', $this->handle, $element->uid]);
        Craft::$app->getCache()->set($cacheKey, ($value ?? []));

        if ($element && $sourceValue && $this->singleField) {
            $relatedSection = Craft::$app->getEntries()->getSectionByUid($sourceValue);

            // Get all the entries that this has already been attached to
            if ($relatedSection) {
                return ManyToMany::$plugin->getService()->getRelatedEntries($element, $relatedSection, $this->singleField);
            }
        }

        return $value;
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

    public function afterElementSave(ElementInterface $element, bool $isNew): void
    {
        ManyToMany::$plugin->getService()->saveRelationship($this, $element);

        parent::afterElementSave($element, $isNew);
    }

    public function getPreviewHtml($value, ElementInterface $element): string
    {
        return Cp::elementPreviewHtml($value);
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
}
