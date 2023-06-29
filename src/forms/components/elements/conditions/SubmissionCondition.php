<?php

namespace BarrelStrength\Sprout\forms\components\elements\conditions;

use craft\elements\conditions\ElementCondition;

class SubmissionCondition extends ElementCondition
{
    protected function conditionRuleTypes(): array
    {
        return array_merge(parent::conditionRuleTypes(), [
            // FormConditionRule::class,
        ]);
    }
}
