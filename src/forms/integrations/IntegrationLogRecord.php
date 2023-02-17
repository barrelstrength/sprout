<?php

namespace BarrelStrength\Sprout\forms\integrations;

use BarrelStrength\Sprout\forms\db\SproutTable;
use craft\db\ActiveRecord;

/**
 * @property $id
 * @property $submissionId
 * @property $integrationId
 * @property $message
 * @property $success
 * @property $status
 */
class IntegrationLogRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return SproutTable::FORM_INTEGRATIONS_LOG;
    }
}
