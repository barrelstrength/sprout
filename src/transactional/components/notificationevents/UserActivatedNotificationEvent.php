<?php

namespace BarrelStrength\Sprout\transactional\components\notificationevents;

use BarrelStrength\Sprout\transactional\notificationevents\ElementEventInterface;
use BarrelStrength\Sprout\transactional\notificationevents\ElementEventTrait;
use BarrelStrength\Sprout\transactional\notificationevents\NotificationEvent;
use Craft;
use craft\base\ElementInterface;
use craft\elements\conditions\users\UserCondition;
use craft\elements\User;
use craft\events\UserEvent;
use craft\helpers\Html;
use craft\services\Users;
use yii\base\Event;

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
        $html = Html::tag('p', Craft::t('sprout-module-transactional','Access the User Element in your email templates using the <code>object</code> variable. Example:'));
        $html .= Html::tag('p', Html::tag('em', Craft::t('sprout-module-transactional', 'This email was sent to: <code>{{ object.email }}</code>')));

        return $html;
    }

    public function getEventObject()
    {
        return $this->event->user;
    }

    public function getMockEventObject()
    {
        return User::find()->one();
    }

    public function matchNotificationEvent(Event $event): bool
    {
        if (!$event instanceof UserEvent) {
            return false;
        }

        return $this->matchElement($event->user);
    }
}
