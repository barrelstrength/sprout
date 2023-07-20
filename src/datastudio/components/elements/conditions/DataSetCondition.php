<?php

namespace BarrelStrength\Sprout\datastudio\components\elements\conditions;

use craft\elements\conditions\ElementCondition;

class DataSetCondition extends ElementCondition
{
    protected function conditionRuleTypes(): array
    {
        return array_merge(parent::conditionRuleTypes(), [
            DataSourcesConditionRule::class,
        ]);
    }
}
