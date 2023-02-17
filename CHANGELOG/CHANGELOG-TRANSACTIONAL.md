# Changelog

## 4.44.444 - UNRELEASED

### Added

- Added support for Craft 4

### Changed

- Migrated `barrelstrength/sprout-base-email` => `barrelstrength/sprout`

### Removed

- *** Removed Sprout Lists Subscriber Element
- *** Removed Sprout Lists List Element
- Removed `barrelstrength/sprout-base-email` dependency
- Removed Setting 'Enable custom Email Templates on a per-email basis'
  Custom Templates must now be defined as Themes and can be selected
- Removed Send Method, CC, and BCC
- Removed SendNotificationEmailEvent. Use default after send events for mail
  action or given target event if needed or we'll add this back if we hear
  complaints.

### Breaking Changes

- Refactored NotificationEvents ?
- Renamed Transactional getEventObject => getObjectVariable()
- Removed support for dynamically setting From Name/Email? Approved Senders now.
- Emails Now we also pass 'recipient' to the template alongside 'object’
- Event `EVENT_REGISTER_EMAIL_EVENT_TYPES` renamed
  => `EVENT_REGISTER_SPROUT_NOTIFICATION_EVENT_TYPES`
- Removed NotificationEvent::getEventHandlerClassName. The event is determined
  dynamically and only matters within each individual NotificationEvent class
  where it can be defined.
- Send Rule now treats the default blank setting as 'Always'
    - And no longer supports the '*'. May need a migration.
    - Users don't need to know as long as it works the same.
- Updated Project Config settings from `sprout-email`
  => `sprout-module-notifications`
- Updated Project Config settings from `sprout-lists` => `sprout-module-lists`
- Updated translation category from `sprout-email`
  => `sprout-module-notifications`
- Updated translation category from `sprout-lists` => `sprout-module-lists`
