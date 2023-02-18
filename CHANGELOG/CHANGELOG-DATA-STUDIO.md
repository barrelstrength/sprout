# Changelog

## 4.44.444 - UNRELEASED

### Added

- Added support for Craft 4
- Added _Product Revenue Data Source_ for Craft Commerce
- Added _Order History Data Source_ for Craft Commerce
- Added Custom Field Layout global setting
- Added `BarrelStrength\Sprout\datastudio\datasources\DateRangeTrait`
- Added `BarrelStrength\Sprout\datastudio\datasources\DateRangeInterface`
- Added `BarrelStrength\Sprout\datastudio\datasources\DateRangeHelper`

### Breaking Changes

- Updated variable `craft.sproutReports.addHeaderRow` => `sprout.twigDataSet.addHeaderRow`
- Updated variable `craft.sproutReports.addRow` => `sprout.twigDataSet.addRow`
- Updated variable `craft.sproutReports.addRows` => `sprout.twigDataSet.addRows`
- Updated DateTime picker syntax in Custom Twig Template Data Sets to use the [date function](https://craftcms.com/docs/4.x/upgrade.html#template-functions)
- Removed Data Source permissions in favor of Craft User permissions
- Removed support for the legacy Category report. Migrate manually.
- Updated Project Config settings from `sprout-reports` => `sprout-module-data-studio`
- Updated translation category from `sprout-reports` => `sprout-module-data-studio`
- Date Sources used as email lists will need to be migrated manually to Audience Types
- Changed dependency `barrelstrength/sprout-base-reports` => `barrelstrength/sprout`

### Changed

- Updated `league/csv` to ^9.8

### Fixed

- Fixed bug where Name Format setting did not get recognized

### Removed

- Removed Report Element `hasNameFormat` attribute
- Removed `barrelstrength/sprout-base-reports` dependency
- Removed permission `sproutreports-editdatasources`
- Removed permission `sproutreports-editsettings`

    
