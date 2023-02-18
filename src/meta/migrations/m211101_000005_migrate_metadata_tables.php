<?php

namespace BarrelStrength\Sprout\meta\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;

class m211101_000005_migrate_metadata_tables extends Migration
{
    public const GLOBAL_METADATA_TABLE = '{{%sprout_global_metadata}}';
    public const OLD_GLOBALS_TABLE = '{{%sproutseo_globals}}';

    public function safeUp(): void
    {
        $cols = [
            'id',
            'siteId',
            'identity',
            'ownership',
            'contacts',
            'social',
            'robots',
            'settings',
            'dateCreated',
            'dateUpdated',
            'uid',
        ];

        if ($this->getDb()->tableExists(self::OLD_GLOBALS_TABLE)) {
            $rows = (new Query())
                ->select($cols)
                ->from([self::OLD_GLOBALS_TABLE])
                ->all();

            Craft::$app->getDb()->createCommand()
                ->batchInsert(self::GLOBAL_METADATA_TABLE, $cols, $rows)
                ->execute();
        }
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
