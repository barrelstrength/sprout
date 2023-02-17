<?php

namespace BarrelStrength\Sprout\mailer\components\elements\audience;

use BarrelStrength\Sprout\mailer\db\SproutTable;
use craft\base\Element;
use craft\db\ActiveRecord;
use craft\records\User;
use yii\db\ActiveQueryInterface;

/**
 * @property int $id
 * @property int $elementId
 * @property int $groupId
 * @property string $audienceType
 * @property string $audienceSettings
 * @property string $name
 * @property string $handle
 * @property ActiveQueryInterface $element
 * @property ActiveQueryInterface $subscribers
 * @property ActiveQueryInterface $listsWithSubscribers
 * @property int $count
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

    public function getSubscribers(): ActiveQueryInterface
    {
        return $this->hasMany(User::class, ['id' => 'itemId'])
            ->viaTable(SproutTable::SUBSCRIPTIONS, ['listId' => 'id']);
    }
}
