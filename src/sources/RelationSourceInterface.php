<?php
namespace verbb\manytomany\sources;

use craft\base\ElementInterface;
use craft\elements\db\ElementQueryInterface;

interface RelationSourceInterface
{
    public function elementType(): string;

    public function label(): string;

    public function sourceType(): string;

    public function sourceLabel(): string;

    public function sourceInstructions(): string;

    public function sourceOptions(): array;

    public function sourceExists(string $sourceValue): bool;

    public function defaultSelectionLabel(): string;

    public function selectorSources(string $sourceValue): array|string;

    public function selectorCriteria(string $sourceValue): array;

    public function findElements(array $ids, ElementInterface $owner, string $sourceValue): array;

    public function findRelatedElements(
        ElementInterface $owner,
        string $sourceValue,
        int $fieldId,
        bool $anyStatus = false,
    ): array;

    public function getEagerLoadingMap(
        array $sourceElements,
        string $sourceValue,
        int $fieldId,
    ): array|false;

    public function getEagerLoadingGqlConditions(string $sourceValue): ?array;

    public function gqlInterface(): string;

    public function gqlArguments(): string;

    public function gqlResolver(): string;

    public function applySource(ElementQueryInterface $query, string $sourceValue): void;

    public function existsQueryCondition(int $fieldId, string $sourceValue, string $namespace): array;
}
