<?php

namespace BarrelStrength\Sprout\forms\integrations;

use BarrelStrength\Sprout\forms\db\SproutTable;
use craft\db\ActiveRecord;

/**
 * @property int $id
 * @property int $formId
 * @property string $name
 * @property string $type
 * @property string $sendRule
 * @property array $settings
 * @property bool $enabled
 */
class IntegrationRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return SproutTable::FORM_INTEGRATIONS;
    }
}
