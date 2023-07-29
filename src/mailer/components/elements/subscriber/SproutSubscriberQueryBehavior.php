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
class SproutSubscriberQueryBehavior extends Behavior
{
    public ?int $sproutSubscriberListId = null;

    public function sproutSubscriberListId(string $value): UserQuery
    {
        $this->sproutSubscriberListId = $value;

        return $this->owner;
    }

    public function events(): array
    {
        return array_merge(parent::events(), [
            UserQuery::EVENT_BEFORE_PREPARE => 'beforePrepare',
        ]);
    }

    public function beforePrepare(): void
    {
        if (!$this->sproutSubscriberListId) {
            return;
        }

        $this->owner->subQuery->innerJoin(
            ['subscriptions' => SproutTable::SUBSCRIPTIONS],
            '[[users.id]] = [[subscriptions.userId]]'
        );

        $this->owner->subQuery->andWhere([
            '[[subscriptions.subscriberListId]]' => $this->sproutSubscriberListId,
        ]);

        $this->owner->subQuery->groupBy(['subscriptions.userId']);
    }
}
