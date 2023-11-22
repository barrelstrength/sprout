# Changelog

## 4.1.6 - UNRELEASED

### Fixed

- Fixed dynamic recipient validation when editing an Email Element
- Fixed issue where sender and reply to values were not being populated for Craft sender behavior
- Fixed retrieval of Mailer Settings in settings area

## 4.1.5 - 2023-11-05

### Changed

- Improved Transactional Email mailer settings validation

### Fixed

- Updated Audience Field to respect setting that enables/disables audiences
- Updated Audience Recipients to respect setting that enables/disables audiences

## 4.1.1 - 2023-09-14

### Added

- Added Mailer to Email Type settings index

### Changed

- Improves Mailer Settings and Email Type migrations
- Improves sender validation when saving Transactional Email Element
- Updates Mailer Settings to support ENV variables when populating defaults
- Removed editable Mailer Field Layout

## 4.1.0 - 2023-09-05

### Added

- Added support for Craft 4
- Added Audience Element
- Added Email Types to configure email templates, custom fields, mailer settings, and permissions
- Added Subscribers as an overlay to Craft Users 
- Added Subscriber List Data Source
- Added Subscriber List Audience Type
- Added User Group Audience Type
- Added support for additional element index table and sort attributes
- Added `twig/cssinliner-extra` dependency `v3.5`

### Changed

- Updated Simple Message Email Templates to Email Message Email Type
- Updated Custom Email Templates to Custom Templates Email Type
- Merged and refactored Sent Email features into Sent Email module
- Merged and refactored Sprout Lists features into Audience Element
- Subscribers now default to non-credentialed Craft Users
- Updated variable `craft.sproutLists` => `sprout.mailer.audiences`
- Updated Project Config settings from `sprout-lists` => `sprout-module-mailer`
- Updated `league/html-to-markdown` dependency `v5.1`

### Removed

- Removed Subscriber Element in favor of inactive Craft Users
- Removed List Element in favor of Audience Element
- Removed Setting 'Enable custom Email Templates on a per-email basis' in favor of Email Types
- Removed Send Method, CC, and BCC fields
