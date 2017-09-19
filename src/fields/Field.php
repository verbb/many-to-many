<?php

namespace OberonAmsterdam\ManyToMany\fields;

use Craft;
use yii\db\Schema;
use craft\base\ElementInterface;
use craft\helpers\Db;
use craft\helpers\Json;

class Field extends craft\base\Field
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
     * getInputHtml
     *
     * @param  [type] $name
     * @param  [type] $value
     * @return [type]
     */
    // public function getInputHtml($name, $value)
    // {
    //     $mtm = craft()->manyToMany;
    //
    //     // Setttings
    //     $source = $this->getSettings()->source;
    //     if (empty($source)) {
    //         return Craft::t('To use the ' . $this->getName() . ' plugin you need to set a source.');
    //     }
    //     $singleField = $this->getSettings()->singleField;
    //     if (empty($singleField)) {
    //         return Craft::t('To use the ' . $this->getName() . ' plugin you need associate it with a related field.');
    //     }
    //
    //     $singleFieldModel = craft()->fields->getFieldById($singleField);
    //     if ($singleFieldModel->translatable) {
    //         return Craft::t('The ' . $this->getName() . ' plugin does not currently work with localized content.');
    //     }
    //
    //     // For this itteration of the plugin, everything is a SECTION, but it's setup so it can be
    //     // refactored in the future to allow for multiple types
    //
    //     if (!is_object($this->element)) {
    //         return Craft::t('For this version of the ' . $this->getName() . ' plugin, you can only use this field with Entries.');
    //     }
    //
    //     $elementType = $this->element->elementType;
    //     if ($elementType != 'Entry') {
    //         return Craft::t('For this version of the ' . $this->getName() . ' plugin, you can only use this field with Entries.');
    //     }
    //     $currentSection = $this->element->sectionId;
    //     $relatedSection = craft()->sections->getSectionById($source['value']);
    //
    //
    //     // Get all the entries that this has already been attached to
    //     $relatedEntries = $mtm->getRelatedEntries($this->element, $relatedSection, $singleField);
    //
    //     // Put related Entries into an array that can be consumed by the JavaScript popup window
    //     $nonSelectable = array();
    //     if (!empty($relatedEntries)) {
    //         foreach ($relatedEntries as $relatedEntry) {
    //             $nonSelectable[] = $relatedEntry->id;
    //         }
    //     }
    //     $nonSelectable = implode(',', $nonSelectable);
    //
    //     $id           = craft()->templates->formatInputId($name);
    //     $namespacedId = craft()->templates->namespaceInputId($id, 'manytomany');
    //
    //     craft()->templates->includeJsResource('manytomany/js/input.js');
    //
    //     return craft()->templates->render('manytomany/input', array(
    //         'name'          => $name,
    //         'value'         => $value,
    //         'id'            => $namespacedId,
    //         'current'       => $relatedEntries,
    //         'section'       => $relatedSection->id,
    //         'nonSelectable' => $nonSelectable,
    //         'singleField'   => $singleField,
    //         'nameSpace'     => craft()->templates->getNamespace(),
    //     ));
    // }

    /**
     * [onAfterElementSave description]
     * @return [type] [description]
     */
    // public function onAfterElementSave()
    // {
    //     craft()->manyToMany->saveRelationship($this);
    // }
}
