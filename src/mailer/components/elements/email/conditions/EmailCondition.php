<?php

namespace BarrelStrength\Sprout\mailer\components\elements\email\conditions;

use craft\elements\conditions\ElementCondition;

abstract class EmailCondition extends ElementCondition
{
    protected function conditionRuleTypes(): array
    {
        return array_merge(parent::conditionRuleTypes(), [
            EmailTypeConditionRule::class,
            PreheaderTextConditionRule::class,
            SubjectLineConditionRule::class,
        ]);
    }
}
