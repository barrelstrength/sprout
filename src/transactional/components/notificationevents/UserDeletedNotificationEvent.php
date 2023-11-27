<?php

namespace BarrelStrength\Sprout\transactional\components\notificationevents;

use BarrelStrength\Sprout\transactional\notificationevents\BaseElementNotificationEvent;
use Craft;
use craft\elements\conditions\users\UserCondition;
use craft\elements\User;

class UserDeletedNotificationEvent extends BaseElementNotificationEvent
{
    public static function displayName(): string
    {
        return Craft::t('sprout-module-transactional', 'When a user is deleted');
    }

    public function getDescription(): string
    {
        return Craft::t('sprout-module-transactional', 'Triggered when a user is deleted.');
    }

    public static function conditionType(): string
    {
        return UserCondition::class;
    }

    public static function elementType(): string
    {
        return User::class;
    }

    public static function getEventClassName(): ?string
    {
        return User::class;
    }

    public static function getEventName(): ?string
    {
        return User::EVENT_AFTER_DELETE;
    }

    public function getTipHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-transactional/_components/notificationevents/user-event-info.md');
    }

    public function getEventVariables(): array
    {
        return [
            'user' => $this?->event?->sender,
        ];
    }

    public function getMockEventVariables(): array
    {
        $user = Craft::$app->getUser()->getIdentity();

        if ($condition = $this->condition) {
            $query = $condition->elementType::find();
            $condition->modifyQuery($query);
            $user = $query->one();
        }

        return [
            'user' => $user,
        ];
    }
}
