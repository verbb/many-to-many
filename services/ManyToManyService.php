<?php
namespace Craft;

class ManyToManyService extends BaseApplicationComponent
{

    var $element;

    /**
     * [getRelatedEntries returns]
     * Returns related entries from an element limited to a section
     * @param  [type] $element
     * @param  [type] $section
     * @return [type]
     */
    public function getRelatedEntries($element, $section, $field)
    {

        $criteria = craft()->elements->getCriteria(ElementType::Entry);
        $criteria->section = $section;
        $criteria->limit   = null;
        $criteria->relatedTo = array(
            'targetElement' => $element,
            'field'         => $field
        );
        $elements = craft()->elements->findElements($criteria);
        return $elements;
        
    }

    /**
     * [saveRelationship description]
     * @param  BaseFieldType $fieldType [description]
     * @return [type]                   [description]
     */
    public function saveRelationship(BaseFieldType $fieldType)
    {
        
        // Delete all the entry caches
        craft()->templateCache->deleteCachesByElementType('Entry');

        // Set the element ID of this element
        $targetId = $fieldType->element->id;

        // Get the post values for this field
        $handle      = $fieldType->model->handle;
        $content     = $fieldType->element->getContent();
        $postContent = $content->getAttribute($handle);

        // There are 3 Items we need to make up a unique relationship in the craft_relations table:
        // fieldId  --> We define this in the Field settings when creating it
        // sourceId --> The elementIds that create the relationship initially. This is currently stored in the $postContent array
        // targetId --> $elementId, this is the reverse of the relationship
        $fieldId = $postContent['singleField'];
        
        // The relationships we either want to add or leave
        $toAdd = array();
        if (!empty($postContent['add'])) {
            $toAdd = $postContent['add'];
        }

        // The relationships we want to remove
        $toDelete = array();
        if (!empty($postContent['delete'])) {
            $toDelete = $postContent['delete'];
        }
        
        // First handle adding or updating the relationships that have to exist
        if (!empty($toAdd)) {
            foreach ($toAdd as $sourceId) {
                
                // 1.) Check and see if this relationship already exists. If it does, do nothing.
                // 2.) If the relationship does NOT exist, create it.
                $exists = craft()->db->createCommand()
                    ->select('id')
                    ->from('relations')
                    ->where('fieldId = :fieldId', array(':fieldId' => $fieldId))
                    ->andWhere('sourceId = :sourceId', array(':sourceId' => $sourceId))
                    ->andWhere('targetId = :targetId', array(':targetId' => $targetId))
                    ->queryColumn();
                
                // The relationship doesn't exist. Add it! For now, the relationship get's added to the beginning
                // of the sort order. This could change.
                if (empty($exists)) {
                    $columns = array(
                        'fieldId'      => $fieldId,
                        'sourceId'     => $sourceId,
                        'sourceLocale' => null,
                        'targetId'     => $targetId,
                        'sortOrder'    => 1);
                    craft()->db->createCommand()->insert('relations', $columns);
                }

            }
        }

        // Now, delete the existing relationships if the user removed them.
        if (!empty($toDelete)) {
            foreach ($toDelete as $sourceId) {

                $oldRelationConditions = array(
                    'and',
                    'fieldId = :fieldId',
                    'sourceId = :sourceId',
                    'targetId = :targetId'
                );
                $oldRelationParams = array(
                    ':fieldId'  => $fieldId,
                    ':sourceId' => $sourceId,
                    ':targetId' => $targetId
                );

                craft()->db->createCommand()->delete('relations', $oldRelationConditions, $oldRelationParams);

            }
        }

    }

}
