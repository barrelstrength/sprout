<?php

namespace BarrelStrength\Sprout\transactional\components\notificationevents;

use BarrelStrength\Sprout\transactional\notificationevents\NotificationEvent;
use Craft;
use craft\elements\Entry;
use craft\events\ModelEvent;
use craft\helpers\ElementHelper;
use yii\base\Event;

class EntriesDeleteNotificationEvent extends NotificationEvent
{
    public static function displayName(): string
    {
        return Craft::t('sprout-module-transactional', 'When an entry is deleted');
    }

    public static function getEventClassName(): ?string
    {
        return Entry::class;
    }

    public static function getEventName(): ?string
    {
        return Entry::EVENT_AFTER_DELETE;
    }

    public function getDescription(): string
    {
        return Craft::t('sprout-module-transactional', 'Triggered when an entry is deleted.');
    }

    public function getEventObject(): ?object
    {
        $event = $this->event ?? null;

        return $event->sender ?? null;
    }

    public function getMockEventObject()
    {
        return Entry::find()->one();
    }

    public function matchNotificationEvent(Event $event): bool
    {
        if (!$event instanceof ModelEvent) {
            return false;
        }

        /** @var Entry $entry */
        $entry = $event->sender;

        if (!$entry instanceof Entry) {
            return false;
        }

        if (ElementHelper::isDraftOrRevision($entry)) {
            return false;
        }

        return true;
    }
}
