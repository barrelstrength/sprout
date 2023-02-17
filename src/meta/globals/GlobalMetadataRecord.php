<?php

namespace BarrelStrength\Sprout\meta\globals;

use BarrelStrength\Sprout\meta\db\SproutTable;
use yii\db\ActiveRecord;

class GlobalMetadataRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return SproutTable::GLOBAL_METADATA;
    }
}
