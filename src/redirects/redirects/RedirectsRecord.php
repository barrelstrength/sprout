<?php

namespace BarrelStrength\Sprout\redirects\redirects;

use BarrelStrength\Sprout\redirects\db\SproutTable;
use craft\db\ActiveRecord;
use craft\records\Element;
use DateTime;
use yii\db\ActiveQueryInterface;

/**
 * @property int $id
 * @property string $oldUrl
 * @property string $newUrl
 * @property int $statusCode
 * @property bool $matchStrategy
 * @property ActiveQueryInterface $element
 * @property int $count
 * @property string $lastRemoteIpAddress
 * @property string $lastReferrer
 * @property string $lastUserAgent
 * @property DateTime $dateLastUsed
 */
class RedirectsRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return SproutTable::REDIRECTS;
    }

    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }
}
