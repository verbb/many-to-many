<?php
namespace verbb\manytomany\services;

use verbb\manytomany\fields\ManyToManyField;

use Craft;
use craft\base\Component;
use craft\base\ElementInterface;
use craft\elements\ElementCollection;
use craft\elements\db\ElementQueryInterface;
use craft\elements\Entry;
use craft\errors\InvalidElementException;
use craft\fields\BaseRelationField;
use craft\helpers\ElementHelper;

class Service extends Component
{
    // Public Methods
    // =========================================================================

    public function prepareRelationshipChanges(ManyToManyField $fieldType, ElementInterface $element, array $desiredIds): ?array
    {
        if (ElementHelper::isDraftOrRevision($element)) {
            return [];
        }

        $sourceValue = $fieldType->source['value'] ?? null;
        $adapter = $fieldType->getSourceAdapter();
        $associatedField = $fieldType->singleField ? Craft::$app->getFields()->getFieldByUid($fieldType->singleField) : null;
        $targetElementType = $associatedField instanceof BaseRelationField ? $associatedField::elementType() : null;

        if (
            !$sourceValue ||
            !$adapter ||
            !$adapter->sourceExists($sourceValue) ||
            !$associatedField instanceof BaseRelationField ||
            !$targetElementType ||
            !$element instanceof $targetElementType ||
            $associatedField->getIsTranslatable(null)
        ) {
            $element->addError($fieldType->handle, Craft::t('manytomany', 'This relationship field is not configured correctly.'));
            return null;
        }

        $desiredElements = $this->_indexElements($adapter->findElements($desiredIds, $element, $sourceValue));

        if (count($desiredElements) !== count($desiredIds)) {
            $element->addError($fieldType->handle, Craft::t('manytomany', 'One or more selected elements are invalid.'));
            return null;
        }

        $currentElements = $this->_indexElements($adapter->findRelatedElements(
            $element,
            $sourceValue,
            $associatedField->id,
            true,
        ));

        if ($fieldType->sourceElementType === Entry::class && !empty($fieldType->entryTypes)) {
            $section = Craft::$app->getEntries()->getSectionByUid($sourceValue);
            $allowedEntryTypeIds = array_flip($fieldType->getAllowedEntryTypeIds($section));

            foreach ($desiredElements as $sourceId => $desiredElement) {
                if (!isset($currentElements[$sourceId]) && !isset($allowedEntryTypeIds[$desiredElement->typeId])) {
                    $element->addError($fieldType->handle, Craft::t('manytomany', 'One or more selected entry types are not allowed.'));
                    return null;
                }
            }
        }

        $toAdd = array_diff_key($desiredElements, $currentElements);
        $toDelete = array_diff_key($currentElements, $desiredElements);

        if (empty($toAdd) && empty($toDelete)) {
            return [];
        }

        $user = Craft::$app->getUser()->getIdentity();
        $elements = Craft::$app->getElements();

        if (!$user) {
            $element->addError($fieldType->handle, Craft::t('manytomany', 'You are not authorized to update the selected elements.'));
            return null;
        }

        // Relations hidden from this editor were not present in the field submission and must remain unchanged.
        $toDelete = array_filter(
            $toDelete,
            fn(ElementInterface $sourceElement) => $elements->canView($sourceElement, $user),
        );
        $modifiedElements = $toAdd + $toDelete;

        if (empty($modifiedElements)) {
            return [];
        }

        $changes = [];

        foreach ($modifiedElements as $sourceId => $sourceElement) {
            $field = $sourceElement->getFieldLayout()?->getFieldByUid($associatedField->uid);

            if (!$field instanceof BaseRelationField || $field::elementType() !== $targetElementType || $field->getIsTranslatable($sourceElement)) {
                $element->addError($fieldType->handle, Craft::t('manytomany', 'One or more selected elements cannot store this relationship.'));
                return null;
            }

            if (!$elements->canView($sourceElement, $user) || !$elements->canSave($sourceElement, $user)) {
                $element->addError($fieldType->handle, Craft::t('manytomany', 'You are not authorized to update the selected elements.'));
                return null;
            }

            $changes[] = [
                'source' => $sourceElement,
                'field' => $field,
                'add' => isset($toAdd[$sourceId]),
            ];
        }

        return $changes;
    }

    public function saveRelationship(ElementInterface $element, array $changes): void
    {
        if (ElementHelper::isDraftOrRevision($element) || empty($changes)) {
            return;
        }

        $targetId = $element->getCanonicalId();

        foreach ($changes as $change) {
            $sourceElement = $change['source'];
            $field = $change['field'];
            $value = $sourceElement->getFieldValue($field->handle);

            if ($value instanceof ElementQueryInterface) {
                $currentRelations = (clone $value)
                    ->status(null)
                    ->siteId('*')
                    ->unique()
                    ->limit(null)
                    ->ids();
            } elseif ($value instanceof ElementCollection) {
                $currentRelations = $value->ids()->all();
            } else {
                $currentRelations = [];
            }

            if ($change['add']) {
                $currentRelations[] = $targetId;
                $currentRelations = array_values(array_unique($currentRelations));
            } else {
                $currentRelations = array_values(array_filter(
                    $currentRelations,
                    fn(int $relationId) => $relationId !== $targetId,
                ));
            }

            $sourceElement->setFieldValue($field->handle, $currentRelations);

            if (!Craft::$app->getElements()->saveElement($sourceElement)) {
                throw new InvalidElementException($sourceElement, Craft::t('manytomany', 'A related element could not be saved.'));
            }
        }

        Craft::$app->getElements()->invalidateCachesForElement($element);
    }


    // Private Methods
    // =========================================================================

    private function _indexElements(array $elements): array
    {
        $indexed = [];

        foreach ($elements as $element) {
            $indexed[$element->id] = $element;
        }

        return $indexed;
    }
}
