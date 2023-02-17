<?php

namespace BarrelStrength\Sprout\mailer\subscriptions;

use BarrelStrength\Sprout\mailer\db\SproutTable;
use craft\db\ActiveRecord;

/**
 * @property int $id
 * @property int $listId
 * @property int $itemId
 */
class SubscriptionRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return SproutTable::SUBSCRIPTIONS;
    }
}
