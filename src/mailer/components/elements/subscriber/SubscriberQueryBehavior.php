<?php

namespace BarrelStrength\Sprout\mailer\components\elements\subscriber;

use BarrelStrength\Sprout\mailer\db\SproutTable;
use craft\elements\db\UserQuery;
use yii\base\Behavior;

/**
 * Extends User Element Query with additional subscriber-specific behaviors
 *
 * @see BehaviorHelper::attachBehaviors() for initialization
 *
 * @property UserQuery $owner
 */
class SubscriberQueryBehavior extends Behavior
{
    public ?int $subscriberListId = null;

    public function events(): array
    {
        return array_merge(parent::events(), [
            UserQuery::EVENT_BEFORE_PREPARE => 'beforePrepare',
        ]);
    }

    public function beforePrepare(): void
    {
        if (!$this->subscriberListId) {
            return;
        }

        $this->owner->subQuery->innerJoin(
            ['subscriptions' => SproutTable::SUBSCRIPTIONS],
            '[[users.id]] = [[subscriptions.itemId]]'
        );

        $this->owner->subQuery->andWhere([
            '[[subscriptions.listId]]' => $this->subscriberListId,
        ]);

        $this->owner->subQuery->groupBy(['subscriptions.itemId']);
    }
}
