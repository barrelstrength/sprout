<?php

namespace BarrelStrength\Sprout\mailer\migrations;

use craft\db\Migration;

class m211101_000005_remove_old_tables_from_db extends Migration
{
    public const OLD_SUBSCRIPTIONS_TABLE = '{{%sproutlists_subscriptions}}';
    public const OLD_SUBSCRIBERS_TABLE = '{{%sproutlists_subscribers}}';
    public const OLD_LISTS_TABLE = '{{%sproutlists_lists}}';

    public function safeUp(): void
    {
        $this->dropTableIfExists(self::OLD_LISTS_TABLE);
        $this->dropTableIfExists(self::OLD_SUBSCRIBERS_TABLE);
        $this->dropTableIfExists(self::OLD_SUBSCRIPTIONS_TABLE);
    }

    public function safeDown(): bool
    {
        echo "m211101_000005_remove_old_tables_from_db cannot be reverted.\n";

        return false;
    }
}
