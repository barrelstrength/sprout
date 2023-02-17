<?php

namespace BarrelStrength\Sprout\transactional\components\notificationevents;

use BarrelStrength\Sprout\transactional\notificationevents\NotificationEvent;
use Craft;
use craft\base\ElementInterface;
use craft\elements\Entry;
use craft\events\ModelEvent;
use craft\helpers\ElementHelper;
use yii\base\Event;

class EntriesSaveNotificationEvent extends NotificationEvent
{
    public bool $whenNew = false;

    public bool $whenUpdated = false;

    public array|string $sectionIds = [];

    public array $availableSections = [];

    public static function displayName(): string
    {
        return Craft::t('sprout-module-transactional', 'When an entry is saved');
    }

    public static function getEventClassName(): ?string
    {
        return Entry::class;
    }

    public static function getEventName(): ?string
    {
        return Entry::EVENT_AFTER_SAVE;
    }

    public function getDescription(): string
    {
        return Craft::t('sprout-module-transactional', 'Triggered when an entry is saved.');
    }

    public function getSettingsHtml(): ?string
    {
        if (!$this->availableSections) {
            $this->availableSections = $this->getAllSections();
        }

        return Craft::$app->getView()->renderTemplate('sprout-module-transactional/_components/events/saveEntry', [
            'event' => $this,
            //            'settings' => $settings,
        ]);
    }

    public function getEventObject(): ?object
    {
        $event = $this->event ?? null;

        return $event->sender ?? null;
    }

    /**
     * @return array|ElementInterface|Entry|null
     */
    public function getMockEventObject()
    {
        $criteria = Entry::find();

        $ids = $this->sectionIds;

        if (is_array($ids) && count($ids)) {

            $id = array_shift($ids);

            $criteria->where([
                'sectionId' => $id,
            ]);
        }

        return $criteria->one();
    }

    public function isSendable(Event $event): bool
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

        if (!$this->matchesWhenCondition($entry)) {
            return false;
        }

        if (!$this->matchesSelectedSectionId($entry)) {
            return false;
        }

        return true;
    }

    public function matchesWhenCondition(Entry $entry): bool
    {

        $isNewLiveEntry = $this->isNewLiveEntry($entry);
        $isUpdatedLiveEntry = $this->isUpdatedLiveEntry($entry);

        if (!$isNewLiveEntry && !$isUpdatedLiveEntry) {
            return false;
        }

        $matchesWhenNew = ($this->whenNew && $isNewLiveEntry) ?? false;
        $matchesWhenUpdated = ($this->whenUpdated && $isUpdatedLiveEntry) ?? false;

        if (!$matchesWhenNew && !$matchesWhenUpdated) {
            return false;
        }

        // Make sure new entries are new.
        if (($this->whenNew && !$isNewLiveEntry) && !$this->whenUpdated) {
            return false;
        }

        // Make sure updated entries are not new
        if (($this->whenUpdated && !$isUpdatedLiveEntry) && !$this->whenNew) {
            return false;
        }

        return true;
    }

    public function matchesSelectedSectionId(Entry $entry): bool
    {
        if (empty($this->sectionIds)) {
            return false;
        }

        if ($this->sectionIds === '*') {
            return true;
        }

        $sectionId = $entry->getSection()->id;

        if (!in_array($sectionId, $this->sectionIds, false)) {
            return false;
        }

        return true;
    }

    // Does not match the scenario where a new entry is disabled
    // or where a new entry has a future publish date.

    protected function isNewLiveEntry(ElementInterface $element): bool
    {
        return
            $element->firstSave &&
            $element->getIsCanonical() &&
            $element->getStatus() === Entry::STATUS_LIVE &&
            !ElementHelper::isDraftOrRevision($element) &&
            !$element->resaving &&
            !$element->propagating;
    }

    // Matches the scenario where a disabled entry gets updated to enabled
    protected function isUpdatedLiveEntry(ElementInterface $element): bool
    {
        return
            !$element->firstSave &&
            $element->getIsCanonical() &&
            $element->getStatus() === Entry::STATUS_LIVE &&
            !ElementHelper::isDraftOrRevision($element) &&
            !$element->resaving &&
            !$element->propagating;
    }

    /**
     * Returns an array of sections suitable for use in checkbox field
     */
    protected function getAllSections(): array
    {
        $result = Craft::$app->sections->getAllSections();
        $options = [];

        foreach ($result as $section) {
            $options[] = [
                'label' => $section->name,
                'value' => $section->id,
            ];
        }

        return $options;
    }
}
