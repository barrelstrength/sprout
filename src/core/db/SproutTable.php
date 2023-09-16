<?php

namespace BarrelStrength\Sprout\core\db;

abstract class SproutTable
{
    /**
     * A general table that any module can save settings to.
     * The controller actions and permissions management will be provided by the module.
     */
    public const SETTINGS = '{{%sprout_settings}}';
}
