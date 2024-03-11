# Changelog

## 4.2.0 - UNRELEASED

### Added

- Added support for Craft 4
- Metadata now renders automatically 

### Changed

- Updated `craft.sproutSeo` variable to `sprout.meta`
- Updated Project Config settings from `sprout-seo` => `sprout-module-meta`
- Updated translation category from `sprout-seo` => `sprout-module-meta`
- Updated `php-science/textrank` dependency v1.2.1,
- Updated Address field to use Craft Address field API

### Removed

- Removed Sprout SEO Element Metadata "Editable Field" option
  - We recommend using existing fields first and falling back to Meta Details Search fields  
- Removed `displayFieldHandles` setting. Craft now supports Field relabeling.