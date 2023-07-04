<?php

namespace BarrelStrength\Sprout\transactional\components\notificationevents;

use BarrelStrength\Sprout\transactional\notificationevents\NotificationEvent;
use BarrelStrength\Sprout\transactional\notificationevents\NotificationEvents;
use Craft;

class ManualNotificationEvent extends NotificationEvent
{
    public static function displayName(): string
    {
        return Craft::t('sprout-module-transactional', 'None');
    }

    public function getDescription(): string
    {
        return Craft::t('sprout-module-transactional', 'The manual event is never triggered.');
    }

    public static function getEventClassName(): ?string
    {
        return NotificationEvents::class;
    }

    public static function getEventName(): ?string
    {
        return NotificationEvents::EVENT_MANUAL_NOTIFICATION_NON_EVENT;
    }
}
