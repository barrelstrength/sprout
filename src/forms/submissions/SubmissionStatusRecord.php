<?php

namespace BarrelStrength\Sprout\forms\submissions;

use BarrelStrength\Sprout\forms\db\SproutTable;
use craft\db\ActiveRecord;

/**
 * @property int $id
 * @property string $cpEditUrl
 * @property string $name
 * @property string $handle
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
