<?php

namespace BarrelStrength\Sprout\transactional\components\elements;

use BarrelStrength\Sprout\core\Sprout;
use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\mailer\components\elements\email\EmailElementQuery;
use BarrelStrength\Sprout\transactional\components\emailtypes\TransactionalEmailEmailType;
use BarrelStrength\Sprout\transactional\notificationevents\NotificationEvent;
use Craft;
use craft\elements\db\ElementQueryInterface;
use yii\base\Behavior;
use yii\base\Event;

/**
 * Extends Email Element with additional transactional email specific behaviors
 *
 * @see NotificationEventHelper::attachBehaviors() for initialization
 *
 * @property EmailElement $owner
 */
class TransactionalEmailElementBehavior extends Behavior
{
    public function getNotificationEvent(Event $event = null): ?NotificationEvent
    {
        if ($event === null) {
            return null;
        }

        /** @var TransactionalEmailEmailType $emailTypeSettings */
        $emailTypeSettings = $this->owner->getEmailTypeSettings();
        $settings = $emailTypeSettings->eventSettings[$event::class] ?? [];

        /** @var NotificationEvent $notificationEvent */
        $notificationEvent = new $emailTypeSettings->eventId();
        $notificationEvent->setAttributes($settings, false);
        $notificationEvent->event = $event;

        return $notificationEvent;
    }
}
