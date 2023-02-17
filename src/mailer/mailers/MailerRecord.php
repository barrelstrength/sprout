<?php

namespace BarrelStrength\Sprout\mailer\mailers;

use BarrelStrength\Sprout\mailer\db\SproutTable;
use craft\db\ActiveRecord;
use craft\gql\types\DateTime;

/**
 * @property int $id
 * @property string $name
 * @property string $type
 * @property string $settings
 * @property DateTime $dateCreated
 * @property DateTime $dateUpdated
 * @property string $uid
 */
class MailerRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return SproutTable::MAILERS;
    }
}
