<?php

namespace BarrelStrength\Sprout\transactional\components\notificationevents;

use BarrelStrength\Sprout\transactional\notificationevents\ElementEventInterface;
use BarrelStrength\Sprout\transactional\notificationevents\ElementEventTrait;
use BarrelStrength\Sprout\transactional\notificationevents\NotificationEvent;
use Craft;
use craft\elements\conditions\users\UserCondition;
use craft\elements\User as UserElement;
use craft\helpers\Html;
use craft\records\User as UserRecord;
use craft\web\User as UserComponent;
use yii\base\Event;
use yii\web\UserEvent;

class UserLoggedInNotificationEvent extends NotificationEvent implements ElementEventInterface
{
    use ElementEventTrait;

    public static function displayName(): string
    {
        return Craft::t('sprout-module-transactional', 'When a user is logged in');
    }

    public function getDescription(): string
    {
        return Craft::t('sprout-module-transactional', 'Triggered when a user is logged in.');
    }

    public static function conditionType(): string
    {
        return UserCondition::class;
    }

    public static function elementType(): string
    {
        return UserElement::class;
    }

    public static function getEventClassName(): ?string
    {
        return UserComponent::class;
    }

    public static function getEventName(): ?string
    {
        return UserComponent::EVENT_AFTER_LOGIN;
    }

    public function getTipHtml(): ?string
    {
        $html = Html::tag('p', Craft::t('sprout-module-transactional', 'Access the User Element in your email templates using the <code>object</code> variable. Example:'));
        $html .= Html::tag('p', Html::tag('em', Craft::t('sprout-module-transactional', 'This email was sent to: <code>{{ object.email }}</code>')));

        return $html;
    }

    public function getEventObject(): mixed
    {
        return $this->event->identity;
    }

    public function getMockEventObject(): mixed
    {
        return UserRecord::find()->one();
    }

    /**
     * Overrides default because the UserEvent is not an ElementEvent
     * but includes the UserElement where we apply our condition rules
     */
    public function matchNotificationEvent(Event $event): bool
    {
        if (!$event instanceof UserEvent) {
            return false;
        }

        return $this->matchElement($event->identity);
    }
}
