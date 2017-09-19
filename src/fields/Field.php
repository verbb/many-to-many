<?php

namespace OberonAmsterdam\ManyToMany\fields;

use Craft;

class Field extends craft\base\Field
{
    /**
     * Get display name for field type dropdown.
     *
     * @return string
     */
    public static function displayName(): string
    {
        return Craft::t('craft-manytomany', 'Many to Many');
    }
    
    // public function defineContentAttribute()
    // {
    //     return false;
    // }

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
     * [defineSettings description]
     * @return [type]
     */
    // protected function defineSettings()
    // {
    //     return array(
    //         'source'       => AttributeType::Mixed,
    //         'singleField'  => AttributeType::Mixed,
    //     );
    // }

    /**
     * [getSettingsHtml description]
     * @return [type]
     */
    // public function getSettingsHtml()
    // {
    //
    //     $allSections = craft()->sections->getAllSections();
    //     $allFields   = craft()->fields->getAllFields();
    //
    //     // Group the Sections into an array
    //     $elements = array();
    //     foreach($allSections as $element)
    //     {
    //         $elements[$element->id] = $element->name;
    //     }
    //
    //     // Group Field Types into an array
    //     $fields = array();
    //     if (!empty($allFields)) {
    //         foreach ($allFields as $field) {
    //             $fields[$field->id] = $field->name;
    //         }
    //     }
    //
    //     // Get the Section Source
    //     $source = $this->getSettings()->source;
    //     if (empty($source)) {
    //         $source = array('type' => '', 'value' => '');
    //     }
    //
    //     // Get the associated Field Type
    //     $singleField = $this->getSettings()->singleField;
    //
    //     return craft()->templates->render(
    //         'manytomany/settings', array(
    //             'source'      => $source,
    //             'singleField' => $singleField,
    //             'elements'    => $elements,
    //             'fields'      => $fields,
    //         )
    //     );
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
