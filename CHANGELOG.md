# Changelog

## 3.0.2 - 2022-12-06

### Fixed
- Migrate field settings to uid instead of ids.

## 3.0.1 - 2022-11-30

### Fixed
- Fix fields not migrating to new package name correctly.

## 3.0.0 - 2022-11-24

### Changed
- Now requires PHP `8.0.2+`.
- Now requires Craft `4.0.0+`.

## 2.0.2 - 2022-12-06

### Fixed
- Migrate field settings to uid instead of ids.

## 2.0.1 - 2022-11-30

### Fixed
- Fix fields not migrating to new package name correctly.

## 2.0.0 - 2022-11-24

> {note} The plugin’s package name has changed to `verbb/many-to-many`. Many To Many will need be updated to 2.0 from a terminal, by running `composer require verbb/many-to-many && composer remove page-8/craft-manytomany`.

### Added
- Add GraphQL support for querying the field.
- Add “Selection Label” to field settings.
- Add preview in CP content index table. (thanks @svale).

### Changed
- Migration to `verbb/many-to-many`.
- Now requires Craft 3.7+.
- Update input.js to use boolean value. (thanks @matthisamoto).
- Refactor field to use `normalizeValue` so it has a proper value for front-end, control panel, element index, and GraphQL.

## 1.0.2.2 - 2018-05-17

### Fixed
- Injected Javascript HTML should reflect updated template HTML.

## 1.0.2.1 - 2018-05-15

### Added
- Update input template to better display elements.

## 0.1.2 - 2016-06-15

### Added
- Translatable text.

## 0.1.1

### Added
- Optimized the cache control. Instead of clearing all Entry types from the cache, just clears records related to the changed element.

## 0.1.0

### Added
- Initial Release.
