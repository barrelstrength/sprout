<?php

namespace BarrelStrength\Sprout\mailer\components\elements\subscriber;

use BarrelStrength\Sprout\mailer\db\SproutTable;
use BarrelStrength\Sprout\mailer\subscribers\SubscriptionRecord;
use craft\elements\db\UserQuery;
use craft\helpers\Db;
use yii\base\Behavior;

class SubscriberElementBehavior extends Behavior
{
    public function getSubscriberListsIds(): array
    {
        $listIds = SubscriptionRecord::find()
            ->select(['listId'])
            ->where(['itemId' => $this->owner->id])
            ->column();

        return $listIds;
    }
}
