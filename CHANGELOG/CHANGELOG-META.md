# Changelog

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