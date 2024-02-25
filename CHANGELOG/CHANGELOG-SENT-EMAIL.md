# Changelog

## 4.1.7 - 2024-02-23

- Fixed permission logic in migrations ([#297])
- Fixed permission logic around Sent Email Element Delete action
- Fixed Send Email Modal resource loading

[#297]: https://github.com/barrelstrength/sprout/issues/297

## 4.1.6 - 2023-11-27

### Added

- Added support for capturing Sent Emails that fail while rendering templates

## 4.1.0 - 2023-09-05

### Added

- Added support for Craft 4
- Added 'Sent' and 'Failed' statuses to Sent Email Element
- Added support for additional element index table and sort attributes

### Changed

- Updated available details captured as sent email info
- Updated Project Config settings from `sprout-sent-email` => `sprout-module-sent-email`
- Updated translation category from `sprout-sent-email` => `sprout-module-sent-email`
- Migrated `barrelstrength/sprout-base-sent-email` => `barrelstrength/sprout`

### Removed

- Removed standalone Sent Email plugin
- Removed `barrelstrength/sprout-base-sent-email` dependency


