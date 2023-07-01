<?php

namespace BarrelStrength\Sprout\transactional\notificationevents;

use Craft;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementCondition;
use craft\helpers\Cp;
use craft\helpers\Html;
use yii\base\Event;
use craft\helpers\Template;
use yii\base\ModelEvent;

trait ElementEventTrait
{
    public array $conditionRules = [];

    public function getSettingsHtml(): ?string
    {
        /** @var ElementCondition $condition */
        $condition = !empty($this->conditionRules)
            ? Craft::$app->conditions->createCondition($this->conditionRules)
            : Craft::createObject(static::conditionType());
        $condition->elementType = static::elementType();
        $condition->sortable = true;
        $condition->mainTag = 'div';
        $condition->name = 'conditionRules';
        $condition->id = 'conditionRules';

        return Cp::fieldHtml($condition->getBuilderHtml(), [
            'label' => Craft::t('sprout-module-transactional', 'Send Rules'),
            'instructions' => Craft::t('sprout-module-transactional', 'Only send an email for events that match the following rules:'),
        ]);
    }

    public function matchNotificationEvent(Event $event): bool
    {
        if (!$event instanceof ModelEvent) {
            return false;
        }

        /** @var ElementInterface $element */
        $element = $event->sender;

        if (!$this->conditionRules) {
            return false;
        }

        $condition = Craft::$app->conditions->createCondition($this->conditionRules);
        $condition->elementType = $element::class;

        return $condition->matchElement($element);
    }
}
