<?php

namespace BarrelStrength\Sprout\transactional\notificationevents;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Cp;
use yii\base\Event;

/**
 * @mixin NotificationEvent
 */
trait ElementEventTrait
{
    public function getExclusiveQueryParams(): array
    {
        return [];
    }

    public function getSettingsHtml(): ?string
    {
        $condition = $this->condition ?? Craft::createObject(static::conditionType());
        $condition->sortable = true;
        $condition->mainTag = 'div';
        $condition->name = 'conditionRules';
        $condition->id = 'conditionRules';

        foreach ($this->getExclusiveQueryParams() as $param) {
            $condition->queryParams[] = $param;
        }

        return Cp::fieldHtml($condition->getBuilderHtml(), [
            'label' => Craft::t('sprout-module-transactional', 'Send Rules'),
            'instructions' => Craft::t('sprout-module-transactional', 'Only send an email for events that match the following rules:'),
        ]);
    }

    /**
     * Returns true if the Email Element's Notification Event matches a given Event
     */
    public function matchNotificationEvent(Event $event): bool
    {
        /** @var ElementInterface $element */
        $element = $event->sender;

        return $this->matchElement($element);
    }

    /**
     * Utility method that builds a condition and checks if an element matches it
     */
    protected function matchElement(ElementInterface $element): bool
    {
        if ($this->condition === null) {
            return true;
        }

        return $this->condition->matchElement($element);
    }
}
