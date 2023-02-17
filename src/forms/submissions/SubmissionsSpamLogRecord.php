<?php

namespace BarrelStrength\Sprout\forms\submissions;

use BarrelStrength\Sprout\forms\db\SproutTable;
use craft\db\ActiveRecord;

/**
 * @property $submissionId
 * @property $type
 * @property $errors
 */
class SubmissionsSpamLogRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return SproutTable::FORM_SUBMISSIONS_SPAM_LOG;
    }
}
