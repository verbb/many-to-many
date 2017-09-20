<?php
/**
 * Many to Many Field Type plugin for Craft CMS 3.x
 *
 * A Field Type plugin for Craft 3 that allows the management of relationships from both sides.
 *
 * @link      https://www.oberon.nl/
 * @copyright Copyright (c) 2017 Oberon Amsterdam
 */

namespace OberonAmsterdam\ManyToMany\Services;

use Craft;
use craft\db\Query;
use craft\elements\Entry;
use craft\models\Section;
use craft\base\Component;
use craft\base\ElementInterface;
use OberonAmsterdam\ManyToMany\Fields\ManyToManyField;

/**
 * @author    Oberon Amsterdam
 * @since     1.0.0
 */
class ManyToManyService extends Component
{
    var $element;

    /**
     * Returns related entries from an element limited to a section.
     *
     * @param ElementInterface $element
     * @param Section $section
     * @param string $field
     *
     * @return Entry[]
     */
    public function getRelatedEntries(ElementInterface $element, Section $section, string $field): array
    {
        $query = Entry::find();

        $query->section = $section;
        $query->limit = null;
        $query->status = null;
        $query->enabledForSite = null;
        $query->relatedTo = [
            'targetElement' => $element,
            'field' => $field,
        ];

        return $query->all();
    }

    /**
     * Save relationships on external fields
     *
     * @param ManyToManyField $fieldType
     * @param ElementInterface $element
     */
    public function saveRelationship(ManyToManyField $fieldType, ElementInterface $element)
    {
        // Set the element ID of this element
        $targetId = $element->getId();

        // Delete cache related to this element ID
        Craft::$app->templateCaches->deleteCachesByElementId($targetId);

        // // Get the post values for this field
        $handle = $fieldType->handle;
        $postContent = $element->getFieldValue($handle);

        // There are 3 Items we need to make up a unique relationship in the craft_relations table:
        // fieldId  --> We define this in the Field settings when creating it
        // sourceId --> The elementIds that create the relationship initially. This is currently stored in the $postContent array
        // targetId --> $elementId, this is the reverse of the relationship
        $fieldId = $postContent['singleField'];

        // The relationships we either want to add or leave
        $toAdd = [];
        if (!empty($postContent['add'])) {
            $toAdd = $postContent['add'];
        }

        // The relationships we want to remove
        $toDelete = [];
        if (!empty($postContent['delete'])) {
            $toDelete = $postContent['delete'];
        }

        // // First handle adding or updating the relationships that have to exist
        if (!empty($toAdd)) {
            foreach ($toAdd as $sourceId) {

                // 1.) Check and see if this relationship already exists. If it does, do nothing.
                // 2.) If the relationship does NOT exist, create it.
                $exists = (new Query())
                    ->select('id')
                    ->from('{{%relations}}')
                    ->where('fieldId = :fieldId', [':fieldId' => $fieldId])
                    ->andWhere('sourceId = :sourceId', [':sourceId' => $sourceId])
                    ->andWhere('targetId = :targetId', [':targetId' => $targetId])
                    ->exists();

                // The relationship doesn't exist. Add it! For now, the relationship get's added to the beginning
                // of the sort order. This could change.
                if (!$exists) {

                    $columns = [
                        'fieldId' => $fieldId,
                        'sourceId' => $sourceId,
                        'sourceSiteId' => null,
                        'targetId' => $targetId,
                        'sortOrder' => 1,
                    ];
                    Craft::$app->db->createCommand()->insert('{{%relations}}', $columns)->execute();
                }

            }
        }

        // Now, delete the existing relationships if the user removed them.
        if (!empty($toDelete)) {
            foreach ($toDelete as $sourceId) {

                $oldRelationConditions = [
                    'and',
                    'fieldId = :fieldId',
                    'sourceId = :sourceId',
                    'targetId = :targetId',
                ];
                $oldRelationParams = [
                    ':fieldId' => $fieldId,
                    ':sourceId' => $sourceId,
                    ':targetId' => $targetId,
                ];

                Craft::$app->db->createCommand()->delete('relations', $oldRelationConditions,
                    $oldRelationParams)->execute();
            }
        }
    }
}
