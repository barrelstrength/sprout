<?php

namespace BarrelStrength\Sprout\forms\integrations;

use BarrelStrength\Sprout\forms\db\SproutTable;
use BarrelStrength\Sprout\forms\integrations\Integration as IntegrationApi;
use craft\db\ActiveRecord;

/**
 * @property $id
 * @property $formId
 * @property $name
 * @property $type
 * @property $sendRule
 * @property $settings
 * @property null|IntegrationApi $integrationApi
 * @property $enabled
 */
class IntegrationRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return SproutTable::FORM_INTEGRATIONS;
    }
}
