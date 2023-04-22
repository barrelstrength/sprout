# Changelog

## 4.0.6 - 2023-04-21

### Fixed

- Fixed edit button permissions ([#8][#8datastudio])
- Fixed Twig Template Query example template paths

[#8datastudio]: https://github.com/barrelstrength/sprout-data-studio/issues/8

## 4.0.5 - 2023-04-01

### Fixed

- Improved multi-site support ([#3][#2datastudio])

[#3datastudio]: https://github.com/barrelstrength/sprout-data-studio/issues/3

## 4.0.4 - 2023-03-20

### Fixed

- Fixed datetime syntax in migration ([#2][#2datastudio])

[#2datastudio]: https://github.com/barrelstrength/craft-sprout-data-studio/issues/2

## 4.0.3 - 2023-02-20

### Fixed

- Fixed display issue on results page
- Updated welcome/upgrade assets

## 4.0.2 - 2023-02-20

### Added

- Added support for Craft 4
- Added _Product Revenue Data Source_ for Craft Commerce
- Added _Order History Data Source_ for Craft Commerce
- Added Custom Field Layout global setting
- Added `BarrelStrength\Sprout\datastudio\components\elements\conditions\DataSourceConditionRule`
- Added `BarrelStrength\Sprout\datastudio\datasources\DateRangeTrait`
- Added `BarrelStrength\Sprout\datastudio\datasources\DateRangeInterface`
- Added `BarrelStrength\Sprout\datastudio\datasources\DateRangeHelper`

### Changed

- Updated variable `craft.sproutReports.addHeaderRow` => `sprout.twigDataSet.addHeaderRow`
- Updated variable `craft.sproutReports.addRow` => `sprout.twigDataSet.addRow`
- Updated variable `craft.sproutReports.addRows` => `sprout.twigDataSet.addRows`
- Updated DateTime picker syntax in Custom Twig Template Data Sets to use the [date function](https://craftcms.com/docs/4.x/upgrade.html#template-functions)
- Updated Project Config settings from `sprout-reports` => `sprout-module-data-studio`
- Updated translation category from `sprout-reports` => `sprout-module-data-studio`
- Date Sources used as email lists will need to be migrated manually to Audience Types
- Migrated `barrelstrength/sprout-base-reports` => `barrelstrength/sprout`
- Updated `league/csv` to ^9.8

### Fixed

- Fixed bug where Name Format setting did not get recognized

### Removed

- Removed Data Source permissions in favor of Craft User permissions
- Removed support for the legacy Category report. Migrate manually.
- Removed Report Element `hasNameFormat` attribute
- Removed `barrelstrength/sprout-base-reports` dependency
- Removed permission `sproutreports-editdatasources`
- Removed permission `sproutreports-editsettings`

    
