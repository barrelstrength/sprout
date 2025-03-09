# Changelog

## 4.2.9 - 2025-03-09

### Fixed

- Fixed issue where some legacy settings could exist in content table data ([#326])

[#326]: https://github.com/barrelstrength/sprout/issues/326

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