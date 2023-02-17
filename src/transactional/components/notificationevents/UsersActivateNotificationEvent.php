<?php

namespace BarrelStrength\Sprout\transactional\components\notificationevents;

use BarrelStrength\Sprout\transactional\notificationevents\NotificationEvent;
use Craft;
use craft\elements\User;
use craft\services\Users;

class UsersActivateNotificationEvent extends NotificationEvent
{
    public static function displayName(): string
    {
        return Craft::t('sprout-module-transactional', 'When a user is activated');
    }

    public static function getEventClassName(): ?string
    {
        return Users::class;
    }

    public static function getEventName(): ?string
    {
        return Users::EVENT_AFTER_ACTIVATE_USER;
    }

    public function getDescription(): string
    {
        return Craft::t('sprout-module-transactional', 'Triggered when a user is activated.');
    }

    public function getEventObject()
    {
        return $this->event->user;
    }

    public function getMockEventObject()
    {
        return User::find()->one();
    }
}
