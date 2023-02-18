<?php

namespace BarrelStrength\Sprout\forms\migrations;

use craft\db\Migration;
use craft\helpers\Db;

class m211101_000000_prep_addresses_table_migration extends Migration
{
    public const OLD_ADDRESSES_TABLE = '{{%sprout_addresses}}';
    public const MIGRATION_ADDRESSES_TABLE = '{{%sprout_addresses_craft3}}';

    /**
     * @todo - confirm this works in workflow. Was first. Now is field specific when run.
     *
     * Prepares the address table before we run the new schema migrations
     */
    public function safeUp(): void
    {
        if ($this->getDb()->tableExists(self::OLD_ADDRESSES_TABLE)) {
            // Clean up existing table
            Db::dropAllForeignKeysToTable(self::OLD_ADDRESSES_TABLE);

            // @todo - update to drop indexes one by one.
            //            Db::dropIndexIfExists(SproutTable::ADDRESSES);

            // Delete any legacy addresses that don't have an Element ID relationship
            $this->delete(self::OLD_ADDRESSES_TABLE, ['elementId' => null]);

            $this->renameTable(self::OLD_ADDRESSES_TABLE, self::MIGRATION_ADDRESSES_TABLE);
        }
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
