# Changelog

## 4.0.5 - 2023-04-01

### Fixed

- Fixed filename casing issue ([#40][#40redirects])

[#40redirects]: https://github.com/barrelstrength/sprout-redirects/issues/40

## 4.0.4 - 2023-03-20

### Fixed

- Fixed datetime syntax in migration

## 4.0.1 - 2023-04-20

### Fixed

- Fixed usability issue for Lite users with isPro logic

## 4.0.0 - 2023-04-20

### Added

- Added support for Craft 4
- Added Custom Field Layout global setting
- Added support for customized sources using Match Strategy and Status Code conditionals
- Added `BarrelStrength\Sprout\redirects\components\elements\conditions\MatchStrategyConditionRule`
- Added `BarrelStrength\Sprout\redirects\components\elements\conditions\StatusCodeConditionRule`

### Changed

- Updated Redirect default ordering to be ‘by Structure’
- Updated Project Config settings from `sprout-redirects` => `sprout-module-redirects`
- Updated translation category from `sprout-redirects` => `sprout-module-redirects`
- Migrated `barrelstrength/sprout-base-redirects` => `barrelstrength/sprout`

### Removed

- Removed `barrelstrength/sprout-base-redirects` dependency
