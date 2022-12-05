<?php
namespace verbb\manytomany\fields;

use verbb\manytomany\ManyToMany;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\PreviewableFieldInterface;
use craft\elements\Entry;
use craft\gql\interfaces\elements\Entry as EntryInterface;
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

    public static function hasContentColumn(): bool
    {
        return false;
    }

    public static function defaultSelectionLabel(): string
    {
        return Craft::t('app', 'Add an entry');
    }


    // Properties
    // =========================================================================

    public array $source = [];
    public ?string $singleField = null;
    public ?string $selectionLabel = null;


    // Public Methods
    // =========================================================================

    public function normalizeValue($value, ElementInterface $element = null): mixed
    {
        $sourceValue = $this->source['value'] ?? null;

        if ($element && $sourceValue && $this->singleField) {
            $relatedSection = Craft::$app->getSections()->getSectionByUid($sourceValue);

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
        foreach (Craft::$app->getSections()->getAllSections() as $section) {
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

    public function getInputHtml($value, ElementInterface $element = null): string
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

        if ($singleFieldModel && $singleFieldModel->getIsTranslatable()) {
            return Craft::t('manytomany', 'The Many to Many plugin does not currently work with localized content.');
        }

        // For this iteration of the plugin, everything is a SECTION, but it's setup, so it can be
        // refactored in the future to allow for multiple types
        if (!($element instanceof Entry)) {
            return Craft::t('manytomany', 'For this version of the Many to Many plugin, you can only use this field with Entries.');
        }

        $relatedSection = Craft::$app->getSections()->getSectionByUid($this->source['value']);

        // Put related Entries into an array that can be consumed by the JavaScript popup window
        $nonSelectable = [];

        if (!empty($value)) {
            foreach ($value as $relatedEntry) {
                $nonSelectable[] = $relatedEntry->id;
            }
        }

        $nonSelectable = implode(',', $nonSelectable);

        $id = Html::id($this->handle);
        $namespacedId = $view->namespaceInputId($id);

        return $view->renderTemplate('manytomany/field/input', [
            'name' => $this->handle,
            'value' => $value,
            'id' => $namespacedId,
            'current' => $value,
            'section' => $relatedSection->uid ?? null,
            'nonSelectable' => $nonSelectable,
            'singleField' => $this->singleField,
            'namespace' => $view->getNamespace(),
            'selectionLabel' => $this->selectionLabel ? Craft::t('site', $this->selectionLabel) : static::defaultSelectionLabel(),
        ]);
    }

    public function afterElementSave(ElementInterface $element, bool $isNew): void
    {
        ManyToMany::$plugin->getService()->saveRelationship($this, $element);

        parent::afterElementSave($element, $isNew);
    }

    public function getTableAttributeHtml($value, ElementInterface $element): string
    {
        if ($value) {
            return Craft::$app->getView()->renderTemplate('_elements/element', [
                'element' => $value[0],
            ]);
        }

        return '';
    }

    public function getContentGqlType(): array
    {
        return [
            'name' => $this->handle,
            'type' => Type::listOf(EntryInterface::getType()),
        ];
    }
}
