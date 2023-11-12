<?php

namespace BarrelStrength\Sprout\transactional\components\notificationevents;

use BarrelStrength\Sprout\transactional\notificationevents\ElementEventInterface;
use BarrelStrength\Sprout\transactional\notificationevents\ElementEventTrait;
use BarrelStrength\Sprout\transactional\notificationevents\NotificationEvent;
use Craft;
use craft\elements\conditions\ElementCondition;
use craft\elements\conditions\users\UserCondition;
use craft\elements\User;
use craft\events\UserEvent;
use craft\helpers\Json;
use craft\services\Users;
use yii\base\Event;

/**
 * @property UserEvent $event
 */
class UserActivatedNotificationEvent extends NotificationEvent implements ElementEventInterface
{
    use ElementEventTrait;

    public static function displayName(): string
    {
        return Craft::t('sprout-module-transactional', 'When a user is activated');
    }

    public function getDescription(): string
    {
        return Craft::t('sprout-module-transactional', 'Triggered when a user is activated.');
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
        return Users::class;
    }

    public static function getEventName(): ?string
    {
        return Users::EVENT_AFTER_ACTIVATE_USER;
    }

    public function getTipHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-transactional/_components/notificationevents/user-event-info.md');
    }

    public function getEventVariables(): array
    {
        return [
            'user' => $this?->event?->user,
        ];
    }

    public function getMockEventVariables(): array
    {
        $user = Craft::$app->getUser()->getIdentity();

        if ($this->conditionRules) {
            $conditionRules = Json::decodeIfJson($this->conditionRules);
            /** @var ElementCondition $condition */
            $condition = Craft::$app->conditions->createCondition($conditionRules);
            $condition->elementType = User::class;

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
        if (!$event instanceof UserEvent) {
            return false;
        }

        return $this->matchElement($event->user);
    }
}
