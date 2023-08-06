<?php

namespace BarrelStrength\Sprout\core\migrations;

use craft\db\Migration;
use craft\db\Query;

class m230806_000000_remove_settings_table extends Migration
{
    public const SETTINGS_TABLE = '{{%sprout_settings}}';

    public function safeUp(): void
    {
        $settingsExist = (new Query())
            ->select('*')
            ->from([self::SETTINGS_TABLE])
            ->exists();

        // Settings should not exist but just in case
        if (!$settingsExist) {
            $this->dropTableIfExists(self::SETTINGS_TABLE);
        }
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
