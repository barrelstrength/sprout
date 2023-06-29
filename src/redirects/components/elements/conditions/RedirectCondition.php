<?php

namespace BarrelStrength\Sprout\redirects\components\elements\conditions;

use craft\elements\conditions\ElementCondition;

class RedirectCondition extends ElementCondition
{

    protected function conditionRuleTypes(): array
    {
        return array_merge(parent::conditionRuleTypes(), [
            MatchStrategyConditionRule::class,
            StatusCodeConditionRule::class,
        ]);
    }
}
