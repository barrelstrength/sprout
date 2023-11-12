<?php

namespace BarrelStrength\Sprout\mailer\components\elements\email;

use BarrelStrength\Sprout\mailer\db\SproutTable;
use craft\base\Element;
use craft\db\ActiveRecord;
use DateTime;
use yii\db\ActiveQueryInterface;

/**
 * @property int $id
 * @property string $subjectLine
 * @property string $preheaderText
 * @property string $defaultMessage
 * @property string $emailTypeUid
 * @property array $mailerInstructionsSettings
 * @property string $emailVariantType
 * @property array $emailVariantSettings
 * @property DateTime|null $dateCreated
 * @property DateTime|null $dateUpdated
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
