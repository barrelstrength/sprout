<?php

namespace BarrelStrength\Sprout\forms\migrations;

use craft\db\Migration;

class m211101_000005_remove_old_tables_from_db extends Migration
{
    public const MIGRATION_ADDRESSES_TABLE = '{{%sprout_addresses_craft3}}';

    public function safeUp(): void
    {
        $this->dropTableIfExists(self::MIGRATION_ADDRESSES_TABLE);
    }

    public function safeDown(): bool
    {
        echo "m211101_000005_remove_old_tables_from_db cannot be reverted.\n";

        return false;
    }
}
