<?php

namespace BarrelStrength\Sprout\sentemail\sentemail;

use BarrelStrength\Sprout\sentemail\db\SproutTable;
use craft\base\Element;
use craft\db\ActiveRecord;
use DateTime;
use yii\db\ActiveQueryInterface;

/**
 * @property int $id
 * @property string $title
 * @property string $subjectLine
 * @property string $fromEmail
 * @property string $fromName
 * @property string $toEmail
 * @property string $textBody
 * @property string $htmlBody
 * @property array $info
 * @property int $originSiteId
 * @property string $originSiteContext
 * @property bool $sent
 * @property string $status
 * @property DateTime|null $dateCreated
 * @property DateTime|null $dateUpdated
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
