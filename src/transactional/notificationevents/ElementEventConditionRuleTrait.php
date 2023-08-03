<?php

namespace BarrelStrength\Sprout\transactional\notificationevents;

use Craft;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementCondition;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\helpers\Cp;
use yii\base\Event;
use yii\db\QueryInterface;

/**
 * Notification Event Element Event conditions are transient and only exist while the event is taking place.
 * We still want to support Element match behavior by extending ElementConditionRuleInterface
 * so this trait is a helper trait that just handles the methods we don't need to do anything with.
 */
trait ElementEventConditionRuleTrait
{
    public function modifyQuery(QueryInterface $query): void
    {
        // No changes
    }
}
