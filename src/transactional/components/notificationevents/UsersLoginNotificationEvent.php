<?php

namespace BarrelStrength\Sprout\transactional\components\notificationevents;

use BarrelStrength\Sprout\transactional\notificationevents\NotificationEvent;
use Craft;
use craft\records\User as UserRecord;
use craft\web\User;

class UsersLoginNotificationEvent extends NotificationEvent
{
    public static function displayName(): string
    {
        return Craft::t('sprout-module-transactional', 'When a user is logged in.');
    }

    public static function getEventClassName(): ?string
    {
        return User::class;
    }

    public static function getEventName(): ?string
    {
        return User::EVENT_AFTER_LOGIN;
    }

    public function getDescription(): string
    {
        return Craft::t('sprout-module-transactional', 'Triggered when a user is logged in.');
    }

    public function getEventObject()
    {
        return $this->event->user;
    }

    public function getMockEventObject()
    {
        return UserRecord::find()->one();
    }
}
