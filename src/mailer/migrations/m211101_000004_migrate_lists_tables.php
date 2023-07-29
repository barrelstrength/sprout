<?php

namespace BarrelStrength\Sprout\mailer\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;

class m211101_000004_migrate_lists_tables extends Migration
{
    public const AUDIENCES_TABLE = '{{%sprout_audiences}}';
    public const SUBSCRIPTIONS_TABLE = '{{%sprout_subscriptions}}';

    public const OLD_SUBSCRIPTIONS_TABLE = '{{%sproutlists_subscriptions}}';
    public const OLD_SUBSCRIBERS_TABLE = '{{%sproutlists_subscribers}}';
    public const OLD_LISTS_TABLE = '{{%sproutlists_lists}}';

    public function safeUp(): void
    {
        $cols = [
            'id',
            'elementId',
            'type',
            'name',
            'handle',
            'dateCreated',
            'dateUpdated',
            'uid',
        ];

        if ($this->getDb()->tableExists(self::OLD_LISTS_TABLE)) {
            $rows = (new Query())
                ->select($cols)
                ->from([self::OLD_LISTS_TABLE])
                ->all();

            Craft::$app->getDb()->createCommand()
                ->batchInsert(
                    self::AUDIENCES_TABLE, $cols, $rows)
                ->execute();
        }

        // @todo - migrate to inactive users
        //        $cols = [
        //            'id',
        //            'userId',
        //            'email',
        //            'firstName',
        //            'lastName',
        //            'dateCreated',
        //            'dateUpdated',
        //            'uid',
        //        ];
        //
        //        if ($this->getDb()->tableExists('{{%sproutlists_subscribers}}')) {
        //            $rows = (new Query())
        //                ->select($cols)
        //                ->from(['{{%sproutlists_subscribers}}'])
        //                ->all();
        //
        //            Craft::$app->getDb()->createCommand()
        //                ->batchInsert(SproutTable::SUBSCRIBERS, $cols, $rows)
        //                ->execute();
        //        }

        $oldCols = [
            'id',
            'listId',
            'itemId',
            'dateCreated',
            'dateUpdated',
            'uid',
        ];

        $newCols = [
            'id',
            'subscriberListId',
            'userId',
            'dateCreated',
            'dateUpdated',
            'uid',
        ];

        if ($this->getDb()->tableExists(self::OLD_SUBSCRIPTIONS_TABLE)) {
            $rows = (new Query())
                ->select($oldCols)
                ->from([self::OLD_SUBSCRIPTIONS_TABLE])
                ->all();

            Craft::$app->getDb()->createCommand()
                ->batchInsert(self::SUBSCRIPTIONS_TABLE, $cols, $rows)
                ->execute();
        }
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
