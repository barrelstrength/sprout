<?php

namespace BarrelStrength\Sprout\forms\integrations;

use BarrelStrength\Sprout\forms\db\SproutTable;
use craft\db\ActiveRecord;

/**
 * @property int $id
 * @property int $submissionId
 * @property int $integrationId
 * @property string $message
 * @property bool $success
 * @property string $status
 */
class IntegrationLogRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return SproutTable::FORM_INTEGRATIONS_LOG;
    }
}
