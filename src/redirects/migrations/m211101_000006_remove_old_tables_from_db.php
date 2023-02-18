<?php

namespace BarrelStrength\Sprout\redirects\migrations;

use craft\db\Migration;

class m211101_000006_remove_old_tables_from_db extends Migration
{
    public const REDIRECTS_TABLE = '{{%sproutseo_redirects}}';

    public function safeUp(): void
    {
        $this->dropTableIfExists(self::REDIRECTS_TABLE);
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
