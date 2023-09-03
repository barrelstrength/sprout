<?php

namespace BarrelStrength\Sprout\redirects\components\elements\conditions;

use craft\elements\conditions\ElementCondition;

class RedirectCondition extends ElementCondition
{
    // Redirects only have a single, global field layout
    // This is necessary for RedirectElement::defineFieldLayouts() to display custom fields when using custom sources
    public ?string $sourceKey = '*';

    protected function conditionRuleTypes(): array
    {
        return array_merge(parent::conditionRuleTypes(), [
            MatchStrategyConditionRule::class,
            StatusCodeConditionRule::class,
        ]);
    }
}
