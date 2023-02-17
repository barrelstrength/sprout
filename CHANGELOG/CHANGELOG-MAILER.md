# Changelog

## 4.44.444 - UNRELEASED

### Added

- Added support for Craft 4
- Added shared components and helpers to support Campaigns, Notifications, and
  Sent Email modules
- Subscribers can now be managed with custom field Element Filters
- Added Subscriber List Audience Type

### Changed

- Sprout Lists is now the Audiences module
- Subscribers are now inactive Craft Users
- Updated `league/html-to-markdown` dependency v5.0.2
- Added `twig/cssinliner-extra` dependency v3.3

### Breaking Changes

- Updates instances `craft.sproutLists` variable to `sprout.audiences`
- Need migration/docs for removed 'Enable User Sync' setting form Sprout Lists.
    - `updateUserIdOnDelete` should now be handled via the LIST Element saving
      the Inactive/Active User ID as a Foreign Key with delete cascade.
- Removed 'Create Subscriber Lists Automatically' enableAutoList setting.
    - Migration path for 'Create Subscriber Lists Automatically' - Maybe get rid
      of this for Tags or Categories?
