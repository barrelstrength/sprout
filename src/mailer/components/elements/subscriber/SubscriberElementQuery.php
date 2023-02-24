<?php

namespace BarrelStrength\Sprout\mailer\components\elements\subscriber;

use BarrelStrength\Sprout\mailer\db\SproutTable;
use craft\elements\db\UserQuery;
use craft\helpers\Db;

class SubscriberElementQuery extends UserQuery
{
    /**
     * Set to true if we don't want to limit query by any Subscriber conditions
     * and are just using SubscriberElementQuery to query Users directly
     */
    public bool $skipSubscriberElementQuery = false;

    public ?int $listId = null;

    public function skipSubscriberElementQuery(bool $value): SubscriberElementQuery
    {
        $this->skipSubscriberElementQuery = $value;

        return $this;
    }

    public function listId(int $value): SubscriberElementQuery
    {
        $this->listId = $value;

        return $this;
    }

    protected function beforePrepare(): bool
    {
        if ($this->skipSubscriberElementQuery) {
            return parent::beforePrepare();
        }

        // Limit query to Users who are subscribed to any list
        $this->subQuery->innerJoin(
            ['subscriptions' => SproutTable::SUBSCRIPTIONS],
            '[[elements.id]] = [[subscriptions.itemId]]'
        );

        $this->subQuery->groupBy(['subscriptions.itemId']);

        if ($this->listId) {
            $this->subQuery->andWhere(Db::parseParam(
                'subscriptions.listId', $this->listId
            ));
        }

        return parent::beforePrepare();
    }
}
