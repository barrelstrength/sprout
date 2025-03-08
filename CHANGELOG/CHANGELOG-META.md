# Changelog

## 5.0.3 - 2025-03-07

### Fixed

- Fixed error when creating address if Website Identity settings are null ([#330])

[#330]: https://github.com/barrelstrength/sprout/issues/330

## 5.0.0-beta.1 - 2024-07-05

### Added

- Added Craft 5 compatibility

## 4.2.5 - 2024-05-24

### Fixed

- Fixed migration issue when read-only mode enabled

## 4.2.4 - 2024-05-18

### Added

- Added Canonical URL Meta Type

### Fixed

- Fixed metadata field template paths for case sensitive systems ([#323])
- Fixed optimized title and description fallback logic when template is null
- Fixed bug where canonical URL was not respected as an override

[#323]: https://github.com/barrelstrength/sprout/issues/323

## 4.2.4 - 2024-05-18

### Added

- Added module path alias

### Changed

- Improved logic when processing images for Schema

### Fixed

- Fixed migration logic for project config workflow

## 4.2.1 - 2024-03-18

### Fixed

- Fixed an issue where Website Identity 'name' value was not being populated
- Fixed rendering of Website Identity 'address' value 

## 4.2.0 - 2024-03-18

### Added

- Added support for Craft 4
- Metadata now renders automatically 
- Added `@yaireo/tagify` dependency

### Changed

- Updated `craft.sproutSeo` variable to `sprout.meta`
- Updated Project Config settings from `sprout-seo` => `sprout-module-meta`
- Updated translation category from `sprout-seo` => `sprout-module-meta`
- Updated `php-science/textrank` dependency v1.2.1,
- Updated Address field to use Craft Address field API

### Removed

- Removed `{% sproutseo 'optimize' %}` tag
- Removed Element Metadata "Editable Field" settings 
- Removed `displayFieldHandles` setting. Craft now supports Field relabeling
- Removed `jquery/tag-editor` dependency