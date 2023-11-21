# Changelog

## 4.1.5 - 2023-11-05

### Changed

- Improved migration logic around custom email template field layouts
- Updated Notification Event tip behavior

## 4.2.2 - 2023-09-15

### Removed

- Removed `BarrelStrength\Sprout\transactional\notificationevents\ElementEventConditionRuleTrait`

## 4.1.0 - 2023-09-05

### Added

- Added support for Craft 4
- Added unified Email Element Edit page
- Added Notification Event Condition Builder
- Added Default Message Native Field for Email Types
- Added customizable Transactional Mailer Settings
- Added `recipient` variable to event variables
- Added `BarrelStrength\Sprout\transactional\components\notificationevents\EntryCreatedNotificationEvent`
- Added `BarrelStrength\Sprout\transactional\components\notificationevents\EntryUpdatedNotificationEvent`
- Added `BarrelStrength\Sprout\transactional\components\notificationevents\UserCreatedNotificationEvent`
- Added `BarrelStrength\Sprout\transactional\components\notificationevents\UserUpdatedNotificationEvent`
- Added `BarrelStrength\Sprout\transactional\components\mailers\TransactionalMailer`
- Added `BarrelStrength\Sprout\transactional\components\elements\conditions\NotificationEventConditionRule`
- Added `BarrelStrength\Sprout\transactional\notificationevents\ElementEventInterface`
- Added `BarrelStrength\Sprout\transactional\notificationevents\ElementEventTrait`

### Changed

- Refactored Notification Event handling
- Moved Sender Info field configuration to Mailer Settings
- Updated default email textarea field `defaultBody` => `defaultMessage`
- Updated Entry and User created and updated events to use `Element::EVENT_AFTER_PROPAGATE`
- Renamed `NotificationEvent::getEventObject()` => `NotificationEvent::getEventVariables()`
- Renamed `NotificationEvent::getMockEventObject()` => `NotificationEvent::getMockEventVariables()`
- Renamed `NotificationEvents::EVENT_REGISTER_EMAIL_EVENT_TYPES` => `NotificationEvents::EVENT_REGISTER_NOTIFICATION_EVENTS`
- Updated Project Config settings from `sprout-email` => `sprout-module-transactional`
- Updated translation category from `sprout-email` => `sprout-module-transactional`
- Migrated `barrelstrength/sprout-base-email` => `barrelstrength/sprout`

### Removed

- Removed Send Rule settings in favor of Notification Event Condition Builder and `TwigExpressionConditionRule`
- Removed `barrelstrength/sprout-base-email` dependency
- Removed `SendNotificationEmailEvent` in favor of default Yii/Craft mailer events


