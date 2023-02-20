# Changelog

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
