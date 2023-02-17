<?php

namespace BarrelStrength\Sprout\mailer\components\elements\subscriber;

use BarrelStrength\Sprout\mailer\db\SproutTable;
use craft\elements\db\UserQuery;
use craft\helpers\Db;

/**
 * Class SubscriberQuery
 *
 * @package BarrelStrength\Sprout\mailer\components\elements\db
 */
class SubscriberElementQuery extends UserQuery
{
    public ?int $listId = null;

    /**
     * @return static self reference
     */
    public function listId(int $value): SubscriberElementQuery
    {
        $this->listId = $value;

        return $this;
    }

    protected function beforePrepare(): bool
    {
        // Limit query to Users who are subscribed to any list
        $this->subQuery->innerJoin(
            ['subscriptions' => SproutTable::SUBSCRIPTIONS],
            '[[subscriptions.itemId]] = [[elements.id]]'
        );

        if ($this->listId) {
            $this->subQuery->andWhere(Db::parseParam(
                'subscriptions.listId', $this->listId
            ));
        }

        return parent::beforePrepare();
    }
}
