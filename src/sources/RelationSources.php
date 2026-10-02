<?php
namespace verbb\manytomany\sources;

use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\User;

class RelationSources
{
    private static ?array $_sources = null;

    public static function all(): array
    {
        return static::$_sources ??= [
            Entry::class => new EntrySource(),
            Category::class => new CategorySource(),
            User::class => new UserSource(),
            Asset::class => new AssetSource(),
        ];
    }

    public static function get(string $elementType): ?RelationSourceInterface
    {
        return static::all()[$elementType] ?? null;
    }
}
