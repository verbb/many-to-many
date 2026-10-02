<?php
namespace verbb\manytomany\sources;

use craft\base\ElementInterface;
use craft\db\Query;
use craft\db\Table;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Db;

use yii\db\Expression;

use DateTime;

abstract class AbstractRelationSource implements RelationSourceInterface
{
    public function selectorCriteria(string $sourceValue): array
    {
        return [];
    }

    public function findElements(array $ids, ElementInterface $owner, string $sourceValue): array
    {
        if (empty($ids)) {
            return [];
        }

        return $this->_query($owner, $sourceValue, true)
            ->id($ids)
            ->fixedOrder()
            ->all();
    }

    public function findRelatedElements(
        ElementInterface $owner,
        string $sourceValue,
        int $fieldId,
        bool $anyStatus = false,
    ): array {
        return $this->_query($owner, $sourceValue, $anyStatus)
            ->relatedTo([
                'targetElement' => $owner,
                'field' => $fieldId,
            ])
            ->limit(null)
            ->all();
    }

    public function getEagerLoadingMap(
        array $sourceElements,
        string $sourceValue,
        int $fieldId,
    ): array|false {
        if (empty($sourceElements)) {
            return false;
        }

        $sourceElementIds = array_map(fn(ElementInterface $element) => $element->id, $sourceElements);
        $sourceSiteId = $sourceElements[0]->siteId;
        $map = (new Query())
            ->select(['relations.targetId as source', 'relations.sourceId as target'])
            ->from(['relations' => Table::RELATIONS])
            ->innerJoin(['relatedElements' => Table::ELEMENTS], '[[relatedElements.id]] = [[relations.sourceId]]')
            ->where([
                'and',
                [
                    'relations.fieldId' => $fieldId,
                    'relations.targetId' => $sourceElementIds,
                    'relatedElements.type' => $this->elementType(),
                    'relatedElements.dateDeleted' => null,
                    'relatedElements.draftId' => null,
                    'relatedElements.revisionId' => null,
                ],
                [
                    'or',
                    ['relations.sourceSiteId' => $sourceSiteId],
                    ['relations.sourceSiteId' => null],
                ],
            ])
            ->all();

        if (empty($map)) {
            return [
                'elementType' => $this->elementType(),
                'map' => [],
                'criteria' => $this->_eagerLoadingCriteria($sourceValue, $sourceSiteId),
            ];
        }

        $targetIds = array_values(array_unique(array_column($map, 'target')));
        $targets = $this->_query($sourceElements[0], $sourceValue, false)
            ->id($targetIds)
            ->limit(null)
            ->all();
        $targetOrder = array_flip(array_map(fn(ElementInterface $element) => (string)$element->id, $targets));

        $map = array_values(array_filter(
            $map,
            fn(array $mapping) => isset($targetOrder[(string)$mapping['target']]),
        ));
        usort($map, fn(array $a, array $b) => $targetOrder[(string)$a['target']] <=> $targetOrder[(string)$b['target']]);

        return [
            'elementType' => $this->elementType(),
            'map' => $map,
            'criteria' => $this->_eagerLoadingCriteria($sourceValue, $sourceSiteId),
        ];
    }

    public function existsQueryCondition(int $fieldId, string $sourceValue, string $namespace): array
    {
        $relationsAlias = "relations_$namespace";
        $elementsAlias = "elements_$namespace";
        $elementsSitesAlias = "elements_sites_$namespace";
        $query = (new Query())
            ->from([$relationsAlias => Table::RELATIONS])
            ->innerJoin([$elementsAlias => Table::ELEMENTS], "[[$elementsAlias.id]] = [[$relationsAlias.sourceId]]")
            ->innerJoin(
                [$elementsSitesAlias => Table::ELEMENTS_SITES],
                "[[$elementsSitesAlias.elementId]] = [[$elementsAlias.id]] AND [[$elementsSitesAlias.siteId]] = [[elements_sites.siteId]]",
            )
            ->where([
                'and',
                "[[$relationsAlias.targetId]] = [[elements.id]]",
                [
                    "$relationsAlias.fieldId" => $fieldId,
                    "$elementsAlias.type" => $this->elementType(),
                    "$elementsAlias.dateDeleted" => null,
                    "$elementsAlias.draftId" => null,
                    "$elementsAlias.revisionId" => null,
                    "$elementsAlias.enabled" => true,
                    "$elementsSitesAlias.enabled" => true,
                ],
                [
                    'or',
                    ["$relationsAlias.sourceSiteId" => null],
                    ["$relationsAlias.sourceSiteId" => new Expression('[[elements_sites.siteId]]')],
                ],
            ]);

        $this->_applyExistsSource($query, $sourceValue, $namespace);

        return ['exists', $query];
    }

    protected function _query(ElementInterface $owner, string $sourceValue, bool $anyStatus): ElementQueryInterface
    {
        $elementType = $this->elementType();
        $query = $elementType::find()
            ->siteId($owner->siteId);

        if ($anyStatus) {
            $query->status(null);
        }

        $this->applySource($query, $sourceValue);

        return $query;
    }

    protected function _eagerLoadingCriteria(string $sourceValue, int $siteId): array
    {
        return array_merge(['siteId' => $siteId], $this->_sourceCriteria($sourceValue));
    }

    protected function _currentTimeDb(): string
    {
        $now = new DateTime();
        $now->setTime((int)$now->format('H'), (int)$now->format('i'), 59);

        return Db::prepareDateForDb($now);
    }

    abstract protected function _sourceCriteria(string $sourceValue): array;

    abstract protected function _applyExistsSource(Query $query, string $sourceValue, string $namespace): void;
}
