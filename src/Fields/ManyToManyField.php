<?php

namespace OberonAmsterdam\ManyToMany\Fields;

use Craft;
use Craft\base\Field;
use craft\elements\Entry;
use craft\base\ElementInterface;
use OberonAmsterdam\ManyToMany\Plugin;

class ManyToManyField extends Field
{
    /** @var array Section Source */
    public $source;
    /** @var string Associated Field Type */
    public $singleField;

    /**
     * Get display name for field type dropdown.
     *
     * @return string
     */
    public static function displayName(): string
    {
        return Craft::t('craft-manytomany', 'Many to Many');
    }

    /**
     * Declare whether or not this field stores data in its own column.
     *
     * @return bool
     */
    public static function hasContentColumn(): bool
    {
        return false;
    }

    /**
     * Define settings.
     *
     * @return array
     */
    public function settingsAttributes(): array
    {
        $attributes = parent::settingsAttributes();
        $attributes[] = 'source';
        $attributes[] = 'singleField';

        return $attributes;
    }

    /**
     * Get template for field type settings.
     *
     * @return string
     */
    public function getSettingsHtml(): string
    {
        $allSections = Craft::$app->sections->getAllSections();
        $allFields = Craft::$app->fields->getAllFields();

        // Group the Sections into an array
        $elements = [];
        foreach ($allSections as $element) {
            $elements[$element->id] = $element->name;
        }

        // Group Field Types into an array
        $fields = [];
        if (!empty($allFields)) {
            /** @var Field $field */
            foreach ($allFields as $field) {
                $fields[$field->id] = $field->name;
            }
        }

        // Get the Section Source
        if (empty($this->source)) {
            $this->source = ['type' => '', 'value' => ''];
        }

        return Craft::$app->getView()->renderTemplate(
            'craft-manytomany/_settings', [
                'source' => $this->source,
                'singleField' => $this->singleField,
                'elements' => $elements,
                'fields' => $fields,
            ]
        );
    }

    /**
     * Product input HTML for edit pages.
     *
     * @param mixed $value
     * @param ElementInterface|null $element
     * @return string
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        $service = Plugin::$plugin->service;
        $plugin = Plugin::$plugin;

        // Validate settings
        if (empty($this->source)) {
            return Craft::t('craft-manytomany', 'To use the ' . $plugin->name . ' plugin you need to set a source.');
        }

        if (empty($this->singleField)) {
            return Craft::t('craft-manytomany',
                'To use the ' . $plugin->name . ' plugin you need associate it with a related field.');
        }

        $singleFieldModel = Craft::$app->fields->getFieldById($this->singleField);
        if ($singleFieldModel->getIsTranslatable()) {
            return Craft::t('craft-manytomany',
                'The ' . $plugin->name . ' plugin does not currently work with localized content.');
        }

        // For this iteration of the plugin, everything is a SECTION, but it's setup so it can be
        // refactored in the future to allow for multiple types

        if (!is_object($element)) {
            return Craft::t('craft-manytomany',
                'For this version of the ' . $plugin->name . ' plugin, you can only use this field with Entries.');
        }

        $elementType = $element->refHandle();
        if ($elementType != 'entry') {
            return Craft::t('craft-manytomany',
                'For this version of the ' . $plugin->name . ' plugin, you can only use this field with Entries.');
        }

        /** @var Entry $element */
        $currentSection = $element->sectionId;
        $relatedSection = Craft::$app->sections->getSectionById($this->source['value']);

        // Get all the entries that this has already been attached to
        $relatedEntries = $service->getRelatedEntries($element, $relatedSection, $this->singleField);

        // Put related Entries into an array that can be consumed by the JavaScript popup window
        $nonSelectable = [];
        if (!empty($relatedEntries)) {
            foreach ($relatedEntries as $relatedEntry) {
                $nonSelectable[] = $relatedEntry->id;
            }
        }
        $nonSelectable = implode(',', $nonSelectable);

        $id = Craft::$app->getView()->formatInputId('field');
        $namespacedId = Craft::$app->getView()->namespaceInputId($id);

        // TODO
        // craft()->templates->includeJsResource('manytomany/js/input.js');

        return Craft::$app->getView()->renderTemplate('craft-manytomany/_input', [
            'name' => 'field',
            'value' => $value,
            'id' => $namespacedId,
            'current' => $relatedEntries,
            'section' => $relatedSection->id,
            'nonSelectable' => $nonSelectable,
            'singleField' => $this->singleField,
            'nameSpace' => Craft::$app->getView()->getNamespace(),
        ]);
    }

    /**
     * [onAfterElementSave description]
     * @return [type] [description]
     */
    // public function onAfterElementSave()
    // {
    //     craft()->manyToMany->saveRelationship($this);
    // }
}
