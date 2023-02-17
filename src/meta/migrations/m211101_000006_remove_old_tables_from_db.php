<?php

namespace BarrelStrength\Sprout\meta\migrations;

use craft\db\Migration;

class m211101_000006_remove_old_tables_from_db extends Migration
{
    public const OLD_GLOBALS_TABLE = '{{%sproutseo_globals}}';

    public function safeUp(): void
    {
        $this->dropTableIfExists(self::OLD_GLOBALS_TABLE);
    }

    public function safeDown(): bool
    {
        echo "m211101_000006_remove_old_tables_from_db cannot be reverted.\n";

        return false;
    }
}
