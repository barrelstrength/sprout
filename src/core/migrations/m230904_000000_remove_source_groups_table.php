<?php

namespace BarrelStrength\Sprout\core\migrations;

use craft\db\Migration;

class m230904_000000_remove_source_groups_table extends Migration
{
    public const SOURCE_GROUPS_TABLE = '{{%sprout_source_groups}}';

    public function safeUp(): void
    {
        $this->dropTableIfExists(self::SOURCE_GROUPS_TABLE);
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
