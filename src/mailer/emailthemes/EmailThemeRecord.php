<?php

namespace BarrelStrength\Sprout\mailer\emailthemes;

use BarrelStrength\Sprout\mailer\db\SproutTable;
use craft\db\ActiveRecord;
use craft\gql\types\DateTime;

/**
 * @property int $id
 * @property string $name
 * @property string $type
 * @property string $htmlEmailTemplate
 * @property string $textEmailTemplate
 * @property string $copyPasteEmailTemplate
 * @property DateTime $dateCreated
 * @property DateTime $dateUpdated
 * @property string $uid
 */
class EmailThemeRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return SproutTable::EMAIL_THEMES;
    }
}
