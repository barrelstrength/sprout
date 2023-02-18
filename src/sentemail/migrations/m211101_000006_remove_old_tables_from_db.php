<?php

namespace BarrelStrength\Sprout\sentemail\migrations;

use craft\db\Migration;

class m211101_000006_remove_old_tables_from_db extends Migration
{
    public const SENT_EMAIL_TABLE = '{{%sproutemail_sentemail}}';

    public function safeUp(): void
    {
        $this->dropTableIfExists(self::SENT_EMAIL_TABLE);
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
