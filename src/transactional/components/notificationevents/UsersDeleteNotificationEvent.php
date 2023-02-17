<?php

namespace BarrelStrength\Sprout\transactional\components\notificationevents;

use BarrelStrength\Sprout\transactional\notificationevents\NotificationEvent;
use Craft;
use craft\elements\User;

class UsersDeleteNotificationEvent extends NotificationEvent
{
    public static function displayName(): string
    {
        return Craft::t('sprout-module-transactional', 'When a user is deleted');
    }

    public static function getEventClassName(): ?string
    {
        return User::class;
    }

    public static function getEventName(): ?string
    {
        return User::EVENT_AFTER_DELETE;
    }

    public function getDescription(): string
    {
        return Craft::t('sprout-module-transactional', 'Triggered when a user is deleted.');
    }

    public function getEventObject(): ?object
    {
        $event = $this->event ?? null;

        return $event->sender ?? null;
    }

    public function getMockEventObject()
    {
        return User::find()->one();
    }
}
