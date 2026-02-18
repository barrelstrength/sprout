# Changelog

## UNRELEASED

### Fixed

- Fixed query string handling in redirect URL when 'Append Query String' enabled ([#353])
- Fixed trailing slash handling in redirect URL when redirecting to home page ([#355])
- Fixed like comparison in Postgres query ([#371])
- Fixed queueing of purge redirects job ([#370])

[#353]: https://github.com/barrelstrength/sprout/issues/353
[#355]: https://github.com/barrelstrength/sprout/issues/355
[#370]: https://github.com/barrelstrength/sprout/issues/370
[#371]: https://github.com/barrelstrength/sprout/issues/371


## 5.0.9 - 2025-02-16

### Fixed

- Fixed settings field layout type assignment ([#362])

[#362]: https://github.com/barrelstrength/sprout/issues/362

## 5.0.6 - 2025-03-21

### Fixed

- Fixed error where migration variable could be null ([#346])

[#346]: https://github.com/barrelstrength/sprout/issues/346

## 5.0.3 - 2025-03-07

### Fixed

- Fixed error when running migrations in read-only environments ([#339])

[#339]: https://github.com/barrelstrength/sprout/issues/339

## 5.0.1 - 2024-06-30

### Fixed 

- Fixed potential logic error in migrations with field layout config settings

## 5.0.0 - 2024-06-30

### Added

- Added Craft 5 compatibility

## 4.2.5 - 2024-05-24

### Fixed

- Fixed migration issue when read-only mode enabled

## 4.2.4 - 2024-05-18

### Added

- Added module path alias

### Fixed

- Fixed migration logic for project config workflow
- Ensures new structureUid exists before cleaning up old values

## 4.1.7 - 2024-02-23

- Adds Support console utility

## 4.1.0 - 2023-09-05

### Changed

- Improved support for custom field criteria custom sources 

## 4.0.8 - 2023-07-20

### Fixed

- Fixed existing 404 cleanup query

## 4.0.7 - 2023-06-29

### Fixed

- Fixed duplicate entry issue in user permission migration

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
