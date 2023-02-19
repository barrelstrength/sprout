<?php

namespace BarrelStrength\Sprout\sentemail\sentemail;

use BarrelStrength\Sprout\sentemail\db\SproutTable;
use craft\base\Element;
use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

/**
 * @property $id int
 * @property $title string
 * @property $subjectLine string
 * @property $fromEmail string
 * @property $fromName string
 * @property $toEmail string
 * @property $textBody string
 * @property $htmlBody string
 * @property $info string
 * @property $status string
 * @property $dateCreated DateTime
 * @property $dateUpdated DateTime
 */
class SentEmailRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return SproutTable::SENT_EMAILS;
    }

    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }
}
