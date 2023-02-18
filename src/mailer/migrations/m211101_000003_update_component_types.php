<?php

namespace BarrelStrength\Sprout\mailer\migrations;

use craft\db\Migration;

class m211101_000003_update_component_types extends Migration
{
    public const AUDIENCES_TABLE = '{{%sprout_audiences}}';

    public function safeUp(): void
    {
        // List Type component has been removed
        // We changes Lists to Audiences so I think we can delete this
        //        if (!$this->db->columnExists(self::AUDIENCES_TABLE, 'type')) {
        //            $this->dropColumn(self::AUDIENCES_TABLE, 'type');
        //        }
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
