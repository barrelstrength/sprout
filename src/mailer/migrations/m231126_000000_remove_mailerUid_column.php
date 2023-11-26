<?php

namespace BarrelStrength\Sprout\mailer\migrations;

use craft\db\Migration;

class m231126_000000_remove_mailerUid_column extends Migration
{
    public const EMAILS_TABLE = '{{%sprout_emails}}';

    public function safeUp(): void
    {
        if (!$this->getDb()->columnExists(self::EMAILS_TABLE, 'mailerUid')) {
            return;
        }

        $this->dropColumn(self::EMAILS_TABLE, 'mailerUid');
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
