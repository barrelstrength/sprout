<?php

namespace BarrelStrength\Sprout\core\migrations;

use craft\db\Migration;

class m221014_000001_rename_settings_table_craft3 extends Migration
{
    public const SETTINGS_TABLE = '{{%sprout_settings}}';
    public const ARCHIVED_SETTINGS_TABLE = '{{%sprout_settings_craft3}}';

    public function safeUp(): void
    {
        if ($this->getDb()->columnExists(self::SETTINGS_TABLE, 'model')) {
            $this->renameTable(self::SETTINGS_TABLE, self::ARCHIVED_SETTINGS_TABLE);
        }
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
