<?php
namespace verbb\manytomany\services;

use verbb\manytomany\fields\ManyToManyField;

use Craft;
use craft\base\Component;
use craft\base\ElementInterface;
use craft\db\Query;
use craft\db\Table;
use craft\elements\Entry;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
use craft\models\Section;

class Service extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * Returns related entries from an element limited to a section.
     *
     * @param ElementInterface $element
     * @param Section $section
     * @param string $field
     *
     * @return Entry[]
     */
    public function getRelatedEntries(ElementInterface $element, Section $section, string $fieldUid): array
    {
        $query = Entry::find();

        $fieldId = Db::idByUid(Table::FIELDS, $fieldUid);

        $query->section = $section;
        $query->limit = null;
        $query->status = null;
        $query->siteId = $element->siteId;
        $query->relatedTo = [
            'targetElement' => $element,
            'field' => $fieldId,
        ];

        return $query->all();
    }

    /**
     * Save relationships on external field.
     *
     * @param ManyToManyField $fieldType
     * @param ElementInterface $element
     */
    public function saveRelationship(ManyToManyField $fieldType, ElementInterface $element): void
    {
        if (ElementHelper::isDraftOrRevision($element)) {
            return;
        }
        
        $cacheKey = implode('--', ['many-to-many', $fieldType->handle, $element->canonicalUid]);
        $content = Craft::$app->getCache()->get($cacheKey);

        // Get element ID of the current element. Be sure to use the canonicalId when dealing with autosave
        $targetId = $element->getCanonicalId();

        // Delete cache related to this element ID
        Craft::$app->getElements()->invalidateCachesForElement($element);

        // Get submitted field value - note that we're fetching `rawValue` not the normalized value
        Craft::$app->getCache()->delete($cacheKey);

        // There are 3 Items we need to make up a unique relationship in the craft_relations table:
        // fieldId  --> We define this in the Field settings when creating it
        // sourceId --> The elementIds that create the relationship initially. This is currently stored in the $content array
        // targetId --> $elementId, this is the reverse of the relationship
        $fieldId = Db::idByUid(Table::FIELDS, $fieldType->singleField);

        $field = Craft::$app->fields->getFieldByUid($fieldType->singleField); 

        // The relationships we either want to add or leave
        $toAdd = !empty($content['add']) ? $content['add'] : [];

        // The relationships we want to remove
        $toDelete = !empty($content['delete']) ? $content['delete'] : [];

        foreach ($toAdd as $sourceId) {
            $sourceElement = Entry::find()->id($sourceId)->status(null)->one();

            if (!$sourceElement) {
                continue;
            }

            $currentRelations = array_map(fn($r) => $r->Id, $sourceElement->getFieldValue($field->handle)->all());
            $currentRelations = array_unique(array_merge($currentRelations, [$targetId]));
            $sourceElement->setFieldValue($field->handle, $currentRelations);
            Craft::$app->elements->saveElement($sourceElement);
        }

        foreach ($toDelete as $sourceId) {
            $sourceElement = Entry::find()->id($sourceId)->status(null)->one();

            if (!$sourceElement) {
                continue;
            }

            $currentRelations = array_map(fn($r) => $r->Id, $sourceElement->getFieldValue($field->handle)->all());
            $currentRelations = array_filter($currentRelations, fn($r) => $r != $targetId);
            $sourceElement->setFieldValue($field->handle, $currentRelations);
            Craft::$app->elements->saveElement($sourceElement);
        }

    }
}
