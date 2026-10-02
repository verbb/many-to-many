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
use craft\fields\Entries;
use craft\helpers\ElementHelper;
use craft\models\Section;

class Service extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * Returns related entries from an element limited to a section.
     *
     * By default this respects Craft’s normal entry status filter (live/enabled).
     * Pass `$anyStatus = true` for CP input so disabled related entries remain visible.
     *
     * @param ElementInterface $element
     * @param Section $section
     * @param string $fieldUid
     * @param bool $anyStatus
     *
     * @return Entry[]
     */
    public function getRelatedEntries(ElementInterface $element, Section $section, string $fieldUid, bool $anyStatus = false): array
    {
        $fieldId = Craft::$app->getFields()->getFieldByUid($fieldUid)?->id;

        if (!$fieldId) {
            return [];
        }

        $query = Entry::find()
            ->section($section)
            ->limit(null)
            ->siteId($element->siteId)
            ->relatedTo([
                'targetElement' => $element,
                'field' => $fieldId,
            ]);

        if ($anyStatus) {
            $query->status(null);
        }

        return $query->all();
    }

    public function prepareRelationshipChanges(ManyToManyField $fieldType, ElementInterface $element, array $desiredIds): ?array
    {
        if (ElementHelper::isDraftOrRevision($element)) {
            return [];
        }

        $sourceValue = $fieldType->source['value'] ?? null;
        $section = $sourceValue ? Craft::$app->getEntries()->getSectionByUid($sourceValue) : null;
        $associatedField = $fieldType->singleField ? Craft::$app->getFields()->getFieldByUid($fieldType->singleField) : null;

        if (!$section || !$associatedField instanceof Entries) {
            $element->addError($fieldType->handle, Craft::t('manytomany', 'This relationship field is not configured correctly.'));
            return null;
        }

        $desiredEntries = empty($desiredIds) ? [] : Entry::find()
            ->id($desiredIds)
            ->siteId($element->siteId)
            ->status(null)
            ->section($section)
            ->fixedOrder()
            ->all();
        $desiredEntries = $this->_indexEntries($desiredEntries);

        if (count($desiredEntries) !== count($desiredIds)) {
            $element->addError($fieldType->handle, Craft::t('manytomany', 'One or more selected entries are invalid.'));
            return null;
        }

        $currentEntries = $this->_indexEntries($this->getRelatedEntries($element, $section, $fieldType->singleField, true));
        $toAdd = array_diff_key($desiredEntries, $currentEntries);
        $toDelete = array_diff_key($currentEntries, $desiredEntries);
        $modifiedEntries = $toAdd + $toDelete;

        if (empty($modifiedEntries)) {
            return [];
        }

        $user = Craft::$app->getUser()->getIdentity();
        $elements = Craft::$app->getElements();
        $changes = [];

        if (!$user) {
            $element->addError($fieldType->handle, Craft::t('manytomany', 'You are not authorized to update the selected entries.'));
            return null;
        }

        foreach ($modifiedEntries as $sourceId => $sourceElement) {
            $field = $sourceElement->getFieldLayout()?->getFieldByUid($associatedField->uid);

            if (!$field instanceof Entries) {
                $element->addError($fieldType->handle, Craft::t('manytomany', 'One or more selected entries cannot store this relationship.'));
                return null;
            }

            if (!$elements->canView($sourceElement, $user) || !$elements->canSave($sourceElement, $user)) {
                $element->addError($fieldType->handle, Craft::t('manytomany', 'You are not authorized to update the selected entries.'));
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
                throw new InvalidElementException($sourceElement, Craft::t('manytomany', 'A related entry could not be saved.'));
            }
        }

        Craft::$app->getElements()->invalidateCachesForElement($element);
    }


    // Private Methods
    // =========================================================================

    private function _indexEntries(array $entries): array
    {
        $indexed = [];

        foreach ($entries as $entry) {
            $indexed[$entry->id] = $entry;
        }

        return $indexed;
    }
}
