<?php

namespace BarrelStrength\Sprout\transactional\migrations;

use craft\db\Migration;

class m211101_000007_remove_old_tables_from_db extends Migration
{
    public const OLD_NOTIFICATIONS_TABLE = '{{%sproutemail_notificationemails}}';

    public function safeUp(): void
    {
        $this->dropTableIfExists(self::OLD_NOTIFICATIONS_TABLE);
    }

    public function safeDown(): bool
    {
        echo "m211101_000007_remove_old_tables_from_db cannot be reverted.\n";

        return false;
    }
}
