<?php
namespace verbb\manytomany\sources;

use Craft;
use craft\db\Query;
use craft\db\Table;
use craft\elements\Category;
use craft\elements\db\ElementQueryInterface;
use craft\gql\arguments\elements\Category as CategoryArguments;
use craft\gql\interfaces\elements\Category as CategoryInterface;
use craft\gql\resolvers\elements\Category as CategoryResolver;
use craft\helpers\Gql;

class CategorySource extends AbstractRelationSource
{
    public function elementType(): string
    {
        return Category::class;
    }

    public function label(): string
    {
        return Craft::t('app', 'Categories');
    }

    public function sourceType(): string
    {
        return 'categoryGroup';
    }

    public function sourceLabel(): string
    {
        return Craft::t('manytomany', 'Linked Category Group');
    }

    public function sourceInstructions(): string
    {
        return Craft::t('manytomany', 'Choose the category group containing the categories that own the associated field.');
    }

    public function sourceOptions(): array
    {
        $options = [];

        foreach (Craft::$app->getCategories()->getAllGroups() as $group) {
            $options[$group->uid] = $group->name;
        }

        return $options;
    }

    public function sourceExists(string $sourceValue): bool
    {
        return Craft::$app->getCategories()->getGroupByUid($sourceValue) !== null;
    }

    public function defaultSelectionLabel(): string
    {
        return Craft::t('app', 'Add a category');
    }

    public function selectorSources(string $sourceValue): array
    {
        return ["group:$sourceValue"];
    }

    public function getEagerLoadingGqlConditions(string $sourceValue): ?array
    {
        if (!in_array($sourceValue, Gql::extractAllowedEntitiesFromSchema()['categorygroups'] ?? [], true)) {
            return null;
        }

        $group = Craft::$app->getCategories()->getGroupByUid($sourceValue);

        return $group ? ['groupId' => [$group->id]] : null;
    }

    public function gqlInterface(): string
    {
        return CategoryInterface::class;
    }

    public function gqlArguments(): string
    {
        return CategoryArguments::class;
    }

    public function gqlResolver(): string
    {
        return CategoryResolver::class;
    }

    public function applySource(ElementQueryInterface $query, string $sourceValue): void
    {
        $query->groupId(Craft::$app->getCategories()->getGroupByUid($sourceValue)?->id ?? 0);
    }

    protected function _sourceCriteria(string $sourceValue): array
    {
        return ['groupId' => Craft::$app->getCategories()->getGroupByUid($sourceValue)?->id ?? 0];
    }

    protected function _applyExistsSource(Query $query, string $sourceValue, string $namespace): void
    {
        $elementsAlias = "elements_$namespace";
        $categoriesAlias = "categories_$namespace";
        $groupId = Craft::$app->getCategories()->getGroupByUid($sourceValue)?->id ?? 0;
        $query
            ->innerJoin([$categoriesAlias => Table::CATEGORIES], "[[$categoriesAlias.id]] = [[$elementsAlias.id]]")
            ->andWhere(["$categoriesAlias.groupId" => $groupId]);
    }
}
