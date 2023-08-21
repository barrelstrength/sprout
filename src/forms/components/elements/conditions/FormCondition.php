<?php

namespace BarrelStrength\Sprout\forms\components\elements\conditions;

use craft\elements\conditions\ElementCondition;

class FormCondition extends ElementCondition
{
    protected function conditionRuleTypes(): array
    {
        return array_merge(parent::conditionRuleTypes(), [
            FormThemeConditionRule::class,
        ]);
    }
}
