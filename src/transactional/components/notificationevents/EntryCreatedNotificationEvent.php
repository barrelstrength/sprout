<?php

namespace BarrelStrength\Sprout\transactional\components\notificationevents;

use BarrelStrength\Sprout\transactional\notificationevents\ElementEventInterface;
use BarrelStrength\Sprout\transactional\notificationevents\ElementEventTrait;
use BarrelStrength\Sprout\transactional\notificationevents\NotificationEvent;
use Craft;
use craft\elements\conditions\ElementCondition;
use craft\elements\conditions\entries\EntryCondition;
use craft\elements\Entry;
use craft\events\ModelEvent;
use craft\helpers\ElementHelper;
use craft\helpers\Json;
use yii\base\Event;
use yii\base\InvalidConfigException;

/**
 * @property ModelEvent $event
 */
class EntryCreatedNotificationEvent extends NotificationEvent implements ElementEventInterface
{
    use ElementEventTrait;

    public static function displayName(): string
    {
        return Craft::t('sprout-module-transactional', 'When an entry is created');
    }

    public function getDescription(): string
    {
        return Craft::t('sprout-module-transactional', 'Triggered when an entry is created.');
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
        return Entry::EVENT_AFTER_PROPAGATE;
    }

    public function getTipHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-transactional/_components/notificationevents/entry-event-info.md');
    }

    public function getEventVariables(): array
    {
        return [
            'entry' => $this?->event?->sender,
        ];
    }

    /**
     * @return array
     * @throws InvalidConfigException
     */
    public function getMockEventVariables(): array
    {
        $entry = null;

        if ($this->conditionRules) {
            $conditionRules = Json::decodeIfJson($this->conditionRules);
            /** @var ElementCondition $condition */
            $condition = Craft::$app->conditions->createCondition($conditionRules);
            $condition->elementType = Entry::class;

            $query = $condition->elementType::find();
            $condition->modifyQuery($query);
            $entry = $query->one();
        }

        return [
            'entry' => $entry,
        ];
    }

    public function matchNotificationEvent(Event $event): bool
    {
        if (!$event instanceof ModelEvent) {
            return false;
        }

        $element = $event->sender;

        $isNewEntryForFirstTime =
            $element->firstSave &&
            $element->getStatus() === Entry::STATUS_LIVE &&
            $element->getIsCanonical() &&
            !ElementHelper::isDraftOrRevision($element);

        $isNewEntryBecauseStatusChangedToLive =
            $element->enabled === true &&
            $element->isAttributeDirty('enabled') &&
            $element->getStatus() === Entry::STATUS_LIVE &&
            $element->getIsCanonical() &&
            !ElementHelper::isDraftOrRevision($element);

        if (!$isNewEntryForFirstTime && !$isNewEntryBecauseStatusChangedToLive) {
            return false;
        }

        return $this->matchElement($element);
    }

    public function getExclusiveQueryParams(): array
    {
        return [
            '_canonicalId',
            'firstSave',
            'draftId',
            'revisionId',
            'resaving',
            'status',
            'propagating',
        ];
    }
}
