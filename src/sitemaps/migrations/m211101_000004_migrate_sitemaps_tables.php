<?php

namespace BarrelStrength\Sprout\sitemaps\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;

class m211101_000004_migrate_sitemaps_tables extends Migration
{
    public const SITEMAPS_TABLE = '{{%sprout_sitemaps_metadata}}';
    public const OLD_SITEMAPS_TABLE = '{{%sproutseo_sitemaps}}';

    public function safeUp(): void
    {
        $cols = [
            'id',
            'siteId',
            'uniqueKey',
            'elementGroupId',
            'enabled',
            'type',
            'uri',
            'priority',
            'changeFrequency',
            'dateCreated',
            'dateUpdated',
            'uid',
        ];

        if ($this->getDb()->tableExists(self::OLD_SITEMAPS_TABLE)) {
            $rows = (new Query())
                ->select($cols)
                ->from([self::OLD_SITEMAPS_TABLE])
                ->all();

            Craft::$app->getDb()->createCommand()
                ->batchInsert(self::SITEMAPS_TABLE, $cols, $rows)
                ->execute();
        }
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
