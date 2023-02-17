# Changelog

## 4.44.444 - UNRELEASED

### Added

- Added support for Craft 4

### Changed

- Migrated `barrelstrength/sprout-base-reports` => `barrelstrength/sprout`

- Updated `league/csv` dependency v9.7.4

### Fixed

- Fixed bug where Name Format setting did not get recognized

### Removed

- Removed Report Element `hasNameFormat` attribute
- Removed `barrelstrength/sprout-base-reports` dependency

### Breaking Changes

- Date Sources used as email lists will need to be manually setup as Audience Types once Sprout Email is released
- Custom Twig Template Query Data Sets that use the DateTime picker like the example need to update the date syntax to
  use Twig [date function](https://craftcms.com/docs/4.x/upgrade.html#template-functions)
- DataSource permissions are now handled as Craft Permissions
- Updated `craft.sproutReports` variable to `sprout.dataSets`
- Updated Project Config settings from `sprout-reports`
  => `sprout-module-reports`
- Updated translation category from `sprout-reports` => `sprout-module-data-studio`
