<?php
namespace verbb\manytomany\fields;

use verbb\manytomany\ManyToMany;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\elements\Entry;
use craft\helpers\Html;

class ManyToManyField extends Field
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


    // Properties
    // =========================================================================

    /**
     * @var array
     */
    public $source = [];

    /**
     * @var null|string
     */
    public $singleField = null;


    // Public Methods
    // =========================================================================

    public function settingsAttributes(): array
    {
        $attributes = parent::settingsAttributes();
        $attributes[] = 'source';
        $attributes[] = 'singleField';

        return $attributes;
    }

    public function getSettingsHtml(): string
    {
        $allSections = Craft::$app->getSections()->getAllSections();
        $allFields = Craft::$app->getFields()->getAllFields() ?? [];

        // Group the Sections into an array
        $elements = [];
        foreach ($allSections as $element) {
            $elements[$element->id] = $element->name;
        }

        // Group Field Types into an array
        $fields = [];
        foreach ($allFields as $field) {
            $fields[$field->id] = $field->name;
        }

        // Get the Section Source
        if (empty($this->source)) {
            $this->source = ['type' => '', 'value' => ''];
        }

        return Craft::$app->getView()->renderTemplate('manytomany/field/settings', [
            'source' => $this->source,
            'singleField' => $this->singleField,
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

        $singleFieldModel = Craft::$app->getFields()->getFieldById($this->singleField);

        if ($singleFieldModel && $singleFieldModel->getIsTranslatable()) {
            return Craft::t('manytomany', 'The Many to Many plugin does not currently work with localized content.');
        }

        // For this iteration of the plugin, everything is a SECTION, but it's setup, so it can be
        // refactored in the future to allow for multiple types
        if (!($element instanceof Entry)) {
            return Craft::t('manytomany', 'For this version of the Many to Many plugin, you can only use this field with Entries.');
        }

        /** @var Entry $element */
        $relatedSection = Craft::$app->getSections()->getSectionById($this->source['value']);

        // Get all the entries that this has already been attached to
        $relatedEntries = ManyToMany::$plugin->getService()->getRelatedEntries($element, $relatedSection, $this->singleField);

        // Put related Entries into an array that can be consumed by the JavaScript popup window
        $nonSelectable = [];

        if (!empty($relatedEntries)) {
            foreach ($relatedEntries as $relatedEntry) {
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
            'current' => $relatedEntries,
            'section' => $relatedSection->uid ?? null,
            'nonSelectable' => $nonSelectable,
            'singleField' => $this->singleField,
            'nameSpace' => $view->getNamespace(),
        ]);
    }

    public function afterElementSave(ElementInterface $element, bool $isNew): void
    {
        ManyToMany::$plugin->getService()->saveRelationship($this, $element);

        parent::afterElementSave($element, $isNew);
    }
}
