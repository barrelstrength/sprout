<?php

namespace BarrelStrength\Sprout\datastudio\datasources;

use craft\base\SavableComponentInterface;

interface DateRangeInterface extends SavableComponentInterface
{
    public const SCENARIO_CUSTOM_RANGE = DateRangeHelper::RANGE_CUSTOM;
}
