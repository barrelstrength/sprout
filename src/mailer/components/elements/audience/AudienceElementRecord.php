<?php

namespace BarrelStrength\Sprout\mailer\components\elements\audience;

use BarrelStrength\Sprout\mailer\db\SproutTable;
use craft\base\Element;
use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

/**
 * @property int $id
 * @property int $elementId
 * @property string $audienceType
 * @property string $audienceSettings
 * @property string $name
 * @property string $handle
 * @property int $count
 * @property ActiveQueryInterface $element
 */
class AudienceElementRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return SproutTable::AUDIENCES;
    }

    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }
}
