<?php

namespace BarrelStrength\Sprout\core\sourcegroups;

use BarrelStrength\Sprout\core\db\SproutTable;
use craft\db\ActiveRecord;

/**
 * @property int $id
 * @property string $type
 * @property string $name
 */
class SourceGroupRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return SproutTable::SOURCE_GROUPS;
    }
}
