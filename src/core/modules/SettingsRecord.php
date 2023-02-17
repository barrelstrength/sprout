<?php

namespace BarrelStrength\Sprout\core\modules;

use BarrelStrength\Sprout\core\db\SproutTable;
use craft\db\ActiveRecord;

/**
 * @property string $id
 * @property string $siteId
 * @property string $moduleId
 * @property string $name
 * @property string $settings
 */
class SettingsRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return SproutTable::SETTINGS;
    }
}
