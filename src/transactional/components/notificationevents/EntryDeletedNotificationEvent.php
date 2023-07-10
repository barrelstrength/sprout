<?php

namespace BarrelStrength\Sprout\transactional\components\notificationevents;

use BarrelStrength\Sprout\transactional\notificationevents\ElementEventInterface;
use BarrelStrength\Sprout\transactional\notificationevents\ElementEventTrait;
use BarrelStrength\Sprout\transactional\notificationevents\NotificationEvent;
use Craft;
use craft\elements\conditions\entries\EntryCondition;
use craft\elements\Entry;
use craft\helpers\Html;

class EntryDeletedNotificationEvent extends NotificationEvent implements ElementEventInterface
{
    use ElementEventTrait;

    public static function displayName(): string
    {
        return Craft::t('sprout-module-transactional', 'When an entry is deleted');
    }

    public function getDescription(): string
    {
        return Craft::t('sprout-module-transactional', 'Triggered when an entry is deleted.');
    }

    public static function conditionType(): string
    {
        return EntryCondition::class;
    }

    public static function elementType(): string
    {
        return Entry::class;
    }

    public static function getEventClassName(): ?string
    {
        return Entry::class;
    }

    public static function getEventName(): ?string
    {
        return Entry::EVENT_AFTER_DELETE;
    }

    public function getTipHtml(): ?string
    {
        $html = Html::tag('p', Craft::t('sprout-module-transactional', 'Access the Entry Element in your email templates using the <code>object</code> variable. Example:'));
        $html .= Html::tag('p', Html::tag('em', Craft::t('sprout-module-transactional', 'The entry "<code>{{ object.title }}</code>" was deleted.')));

        return $html;
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
}
