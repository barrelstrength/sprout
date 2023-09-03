<?php

namespace BarrelStrength\Sprout\datastudio\components\elements\conditions;

use craft\elements\conditions\ElementCondition;

class DataSetCondition extends ElementCondition
{
    // Data Sets only have a single, global field layout
    // This is necessary for DataSetElement::defineFieldLayouts() to display custom fields when using custom sources
    public ?string $sourceKey = '*';

    protected function conditionRuleTypes(): array
    {
        return array_merge(parent::conditionRuleTypes(), [
            DataSourcesConditionRule::class,
        ]);
    }
}
