<?php

namespace BarrelStrength\Sprout\core\helpers;

use BarrelStrength\Sprout\core\components\elements\conditions\TwigExpressionConditionRule;
use BarrelStrength\Sprout\transactional\components\elements\TransactionalEmailElement;
use craft\events\RegisterConditionRuleTypesEvent;

class ConditionHelper
{
    public static function registerConditionRuleTypes(RegisterConditionRuleTypesEvent $event): void
    {
        if (!$event->sender->elementType === TransactionalEmailElement::class) {
            return;
        }

        // Feature Request: Is there a way to indicate a condition does not modify Element queries
        $event->conditionRuleTypes[] = TwigExpressionConditionRule::class;
    }
}

