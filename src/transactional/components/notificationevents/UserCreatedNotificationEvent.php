<?php

namespace BarrelStrength\Sprout\transactional\components\notificationevents;

use BarrelStrength\Sprout\transactional\notificationevents\ElementEventInterface;
use BarrelStrength\Sprout\transactional\notificationevents\ElementEventTrait;
use BarrelStrength\Sprout\transactional\notificationevents\NotificationEvent;
use Craft;
use craft\elements\conditions\users\UserCondition;
use craft\elements\User;
use craft\events\ModelEvent;
use craft\helpers\Html;
use craft\helpers\Json;
use yii\base\Event;

class UserCreatedNotificationEvent extends NotificationEvent implements ElementEventInterface
{
    use ElementEventTrait;

    public static function displayName(): string
    {
        return Craft::t('sprout-module-transactional', 'When a new user is created');
    }

    public function getDescription(): string
    {
        return Craft::t('sprout-module-transactional', 'Triggered when a new user is created.');
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
        $html = Html::tag('p', Craft::t('sprout-module-transactional', 'Access the User Element in your email templates using the <code>object</code> variable. Example:'));
        $html .= Html::tag('p', Html::tag('em', Craft::t('sprout-module-transactional', 'This email was sent to: <code>{{ object.email }}</code>')));

        return $html;
    }

    public function getEventObject(): mixed
    {
        $event = $this->event ?? null;

        return $event->sender ?? null;
    }

    public function getMockEventObject(): mixed
    {
        $user = Craft::$app->getUser()->getIdentity();

        if ($this->conditionRules) {
            $conditionRules = Json::decodeIfJson($this->conditionRules);
            $condition = Craft::$app->conditions->createCondition($conditionRules);
            $condition->elementType = User::class;

            $query = $condition->elementType::find();
            $condition->modifyQuery($query);

            $user = $query->one();
        }

        return $user;
    }

    public function matchNotificationEvent(Event $event): bool
    {
        if (!$event instanceof ModelEvent) {
            return false;
        }

        if (!$event->isNew) {
            return false;
        }

        return $this->matchElement($event->sender);
    }
}
