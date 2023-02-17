<?php

namespace BarrelStrength\Sprout\forms\submissions;

use BarrelStrength\Sprout\forms\db\SproutTable;
use craft\db\ActiveRecord;

/**
 * @property int $id     ID
 * @property string $cpEditUrl
 * @property string $name   Name
 * @property string $handle Handle
 * @property string $color
 * @property int $sortOrder
 * @property bool $isDefault
 */
class SubmissionStatusRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return SproutTable::FORM_SUBMISSIONS_STATUSES;
    }
}
