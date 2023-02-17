# Changelog

## 4.44.444 - UNRELEASED

### Added

- Added support for Craft 4

### Changed

- Updated `php-science/textrank` dependency v1.2.1,

### Removed

- Updated `OnSaveAddressEvent::$source` => `OnSaveAddressEvent::$element`
  Commit: 78cd228838f3ac5efabd8d536e738b26511bebd2
- Removed `OnSaveAddressEvent`
- Removed `OnSaveAddressEvent::$model`
- Removed Sprout SEO Element Metadata "Editable Field" option
- Removed displayFieldHandles setting

### Breaking Changes

- Updated `craft.sproutSeo` variable to `sprout.meta`
- Updated Project Config settings from `sprout-seo` => `sprout-module-meta`
- Updated translation category from `sprout-seo` => `sprout-module-meta`
