<?php

namespace BarrelStrength\Sprout\mailer\components\elements\email;

use BarrelStrength\Sprout\mailer\db\SproutTable;
use craft\base\Element;
use craft\db\ActiveRecord;
use craft\gql\types\DateTime;
use yii\db\ActiveQueryInterface;

/**
 * @property int $id
 * @property string $subjectLine
 * @property string $preheaderText
 * @property string $defaultBody
 * @property string $emailThemeUid
 * @property string $mailerId
 * @property string $mailerInstructionsSettings
 * @property string $emailType
 * @property string $emailTypeSettings
 * @property DateTime $dateCreated
 * @property DateTime $dateUpdated
 * @property string $uid
 *
 * @property ActiveQueryInterface $element
 */
class EmailElementRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return SproutTable::EMAILS;
    }

    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }
}
