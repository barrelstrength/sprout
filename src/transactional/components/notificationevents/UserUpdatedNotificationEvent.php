<?php

namespace BarrelStrength\Sprout\transactional\components\notificationevents;

use BarrelStrength\Sprout\transactional\notificationevents\BaseElementNotificationEvent;
use Craft;
use craft\elements\conditions\users\UserCondition;
use craft\elements\User;
use craft\events\ModelEvent;
use yii\base\Event;

/**
 * @property ModelEvent $event
 */
class UserUpdatedNotificationEvent extends BaseElementNotificationEvent
{
    public static function displayName(): string
    {
        return Craft::t('sprout-module-transactional', 'When a user is updated');
    }

    public function getDescription(): string
    {
        return Craft::t('sprout-module-transactional', 'Triggered when an existing user is saved.');
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
        return User::EVENT_AFTER_PROPAGATE;
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

    public function matchNotificationEvent(Event $event): bool
    {
        if (!$event instanceof ModelEvent) {
            return false;
        }

        if ($event->isNew) {
            return false;
        }

        return $this->matchElement($event->sender);
    }
}
