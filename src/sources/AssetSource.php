<?php
namespace verbb\manytomany\sources;

use Craft;
use craft\db\Query;
use craft\db\Table;
use craft\elements\Asset;
use craft\elements\db\ElementQueryInterface;
use craft\gql\arguments\elements\Asset as AssetArguments;
use craft\gql\interfaces\elements\Asset as AssetInterface;
use craft\gql\resolvers\elements\Asset as AssetResolver;
use craft\helpers\Gql;

class AssetSource extends AbstractRelationSource
{
    public function elementType(): string
    {
        return Asset::class;
    }

    public function label(): string
    {
        return Craft::t('app', 'Assets');
    }

    public function sourceType(): string
    {
        return 'volume';
    }

    public function sourceLabel(): string
    {
        return Craft::t('manytomany', 'Linked Volume');
    }

    public function sourceInstructions(): string
    {
        return Craft::t('manytomany', 'Choose the volume containing the assets that own the associated field.');
    }

    public function sourceOptions(): array
    {
        $options = [];

        foreach (Craft::$app->getVolumes()->getAllVolumes() as $volume) {
            $options[$volume->uid] = $volume->name;
        }

        return $options;
    }

    public function sourceExists(string $sourceValue): bool
    {
        return Craft::$app->getVolumes()->getVolumeByUid($sourceValue) !== null;
    }

    public function defaultSelectionLabel(): string
    {
        return Craft::t('app', 'Add an asset');
    }

    public function selectorSources(string $sourceValue): array
    {
        return ["volume:$sourceValue"];
    }

    public function getEagerLoadingGqlConditions(string $sourceValue): ?array
    {
        if (!in_array($sourceValue, Gql::extractAllowedEntitiesFromSchema()['volumes'] ?? [], true)) {
            return null;
        }

        $volume = Craft::$app->getVolumes()->getVolumeByUid($sourceValue);

        return $volume ? ['volumeId' => [$volume->id]] : null;
    }

    public function gqlInterface(): string
    {
        return AssetInterface::class;
    }

    public function gqlArguments(): string
    {
        return AssetArguments::class;
    }

    public function gqlResolver(): string
    {
        return AssetResolver::class;
    }

    public function applySource(ElementQueryInterface $query, string $sourceValue): void
    {
        $query->volumeId(Craft::$app->getVolumes()->getVolumeByUid($sourceValue)?->id ?? 0);
    }

    protected function _sourceCriteria(string $sourceValue): array
    {
        return ['volumeId' => Craft::$app->getVolumes()->getVolumeByUid($sourceValue)?->id ?? 0];
    }

    protected function _applyExistsSource(Query $query, string $sourceValue, string $namespace): void
    {
        $elementsAlias = "elements_$namespace";
        $assetsAlias = "assets_$namespace";
        $volumeId = Craft::$app->getVolumes()->getVolumeByUid($sourceValue)?->id ?? 0;
        $query
            ->innerJoin([$assetsAlias => Table::ASSETS], "[[$assetsAlias.id]] = [[$elementsAlias.id]]")
            ->andWhere(["$assetsAlias.volumeId" => $volumeId]);
    }
}
