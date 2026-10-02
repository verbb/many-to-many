<?php
namespace verbb\manytomany\sources;

use Craft;
use craft\db\Query;
use craft\db\Table;
use craft\elements\db\ElementQueryInterface;
use craft\elements\Entry;
use craft\gql\arguments\elements\Entry as EntryArguments;
use craft\gql\interfaces\elements\Entry as EntryInterface;
use craft\gql\resolvers\elements\Entry as EntryResolver;
use craft\helpers\Gql;

class EntrySource extends AbstractRelationSource
{
    public function elementType(): string
    {
        return Entry::class;
    }

    public function label(): string
    {
        return Craft::t('app', 'Entries');
    }

    public function sourceType(): string
    {
        return 'section';
    }

    public function sourceLabel(): string
    {
        return Craft::t('manytomany', 'Linked Section');
    }

    public function sourceInstructions(): string
    {
        return Craft::t('manytomany', 'Choose the section containing the entries that own the associated field.');
    }

    public function sourceOptions(): array
    {
        $options = [];

        foreach (Craft::$app->getEntries()->getAllSections() as $section) {
            $options[$section->uid] = $section->name;
        }

        return $options;
    }

    public function sourceExists(string $sourceValue): bool
    {
        return Craft::$app->getEntries()->getSectionByUid($sourceValue) !== null;
    }

    public function defaultSelectionLabel(): string
    {
        return Craft::t('app', 'Add an entry');
    }

    public function selectorSources(string $sourceValue): array
    {
        return ["section:$sourceValue"];
    }

    public function getEagerLoadingGqlConditions(string $sourceValue): ?array
    {
        $allowedEntities = Gql::extractAllowedEntitiesFromSchema();

        if (!in_array($sourceValue, $allowedEntities['sections'] ?? [], true)) {
            return null;
        }

        $section = Craft::$app->getEntries()->getSectionByUid($sourceValue);

        return $section ? ['sectionId' => [$section->id]] : null;
    }

    public function gqlInterface(): string
    {
        return EntryInterface::class;
    }

    public function gqlArguments(): string
    {
        return EntryArguments::class;
    }

    public function gqlResolver(): string
    {
        return EntryResolver::class;
    }

    public function applySource(ElementQueryInterface $query, string $sourceValue): void
    {
        $query->sectionId(Craft::$app->getEntries()->getSectionByUid($sourceValue)?->id ?? 0);
    }

    protected function _sourceCriteria(string $sourceValue): array
    {
        return ['sectionId' => Craft::$app->getEntries()->getSectionByUid($sourceValue)?->id ?? 0];
    }

    protected function _applyExistsSource(Query $query, string $sourceValue, string $namespace): void
    {
        $elementsAlias = "elements_$namespace";
        $entriesAlias = "entries_$namespace";
        $sectionId = Craft::$app->getEntries()->getSectionByUid($sourceValue)?->id ?? 0;
        $currentTimeDb = $this->_currentTimeDb();
        $query
            ->innerJoin([$entriesAlias => Table::ENTRIES], "[[$entriesAlias.id]] = [[$elementsAlias.id]]")
            ->andWhere(["$entriesAlias.sectionId" => $sectionId])
            ->andWhere(['<=', "$entriesAlias.postDate", $currentTimeDb])
            ->andWhere([
                'or',
                ["$entriesAlias.expiryDate" => null],
                ['>', "$entriesAlias.expiryDate", $currentTimeDb],
            ]);
    }
}
