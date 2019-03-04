<?php

namespace Page8\ManyToMany\fields;

use Craft;
use Craft\base\Field;
use craft\base\PreviewableFieldInterface;
use craft\elements\Entry;
use Page8\ManyToMany\Plugin;
use craft\base\ElementInterface;

/**
 * @property string $settingsHtml
 */
class ManyToManyField extends Field implements PreviewableFieldInterface
{
    /**
     * Section source
     * @var array
     */
    public $source;

    /**
     * Associated field type
     * @var string
     */
    public $singleField;

    /**
     * Get display name for field type.
     *
     * @return string
     */
    public static function displayName(): string
    {
        return Craft::t('manytomany', 'Many to Many');
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
        $allFields = Craft::$app->fields->getAllFields() ?? [];

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

        return Craft::$app->view->renderTemplate(
            'manytomany/_settings', [
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
        $plugin = Plugin::getInstance();
        $service = $plugin->service;

        // Validate settings
        if (empty($this->source)) {
            return Craft::t('manytomany', 'To use the {pluginName} plugin you need to set a source.',
                ['pluginName' => $plugin->name]);
        }

        if (empty($this->singleField)) {
            return Craft::t('manytomany',
                'To use the {pluginName} plugin you need associate it with a related field.',
                ['pluginName' => $plugin->name]);
        }

        $singleFieldModel = Craft::$app->fields->getFieldById($this->singleField);
        if ($singleFieldModel->getIsTranslatable()) {
            return Craft::t('manytomany',
                'The {pluginName} plugin does not currently work with localized content.',
                ['pluginName' => $plugin->name]);
        }

        // For this iteration of the plugin, everything is a SECTION, but it's setup so it can be
        // refactored in the future to allow for multiple types

        if (!is_object($element) || $element->refHandle() != 'entry') {
            return Craft::t('manytomany',
                'For this version of the {pluginName} plugin, you can only use this field with Entries.',
                ['pluginName' => $plugin->name]);
        }

        /** @var Entry $element */
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

        $id = Craft::$app->view->formatInputId($this->handle);
        $namespacedId = Craft::$app->view->namespaceInputId($id);

        return Craft::$app->view->renderTemplate('manytomany/_input', [
            'name' => $this->handle,
            'value' => $value,
            'id' => $namespacedId,
            'current' => $relatedEntries,
            'section' => $relatedSection->uid,
            'nonSelectable' => $nonSelectable,
            'singleField' => $this->singleField,
            'nameSpace' => Craft::$app->view->getNamespace(),
        ]);
    }

    /**
     * Save relationships on external field.
     *
     * @inheritdoc
     */
    public function afterElementSave(ElementInterface $element, bool $isNew)
    {
        Plugin::getInstance()->service->saveRelationship($this, $element);

        parent::afterElementSave($element, $isNew);
    }

    /**
     * Picks the first entry of a revers relationship (if any) and displays as link in the CP content table list
     *
     * @inheritdoc
     */
    public function getTableAttributeHtml($value, ElementInterface $element): string
    {
        $plugin = Plugin::getInstance();
        $service = $plugin->service;

        $relatedSection = Craft::$app->sections->getSectionById($this->source['value']);
        $relatedEntries = $service->getRelatedEntries($element, $relatedSection, $this->singleField);

        $element = $relatedEntries[0] ?? null;

        if ($element) {
            return Craft::$app->getView()->renderTemplate('_elements/element', [
                'element' => $element
            ]);
        }
        return '';
    }

}