<?php

namespace BarrelStrength\Sprout\datastudio\migrations;

use craft\db\Migration;

class m211101_000009_remove_old_tables_from_db extends Migration
{
    public const OLD_DATASOURCES_TABLE = '{{%sproutreports_datasources}}';
    public const OLD_REPORTS_TABLE = '{{%sproutreports_reports}}';
    public const OLD_REPORTS_GROUPS_TABLE = '{{%sproutreports_reportgroups}}';

    public function safeUp(): void
    {
        $this->dropTableIfExists(self::OLD_REPORTS_TABLE);
        $this->dropTableIfExists(self::OLD_REPORTS_GROUPS_TABLE);
        $this->dropTableIfExists(self::OLD_DATASOURCES_TABLE);
    }

    public function safeDown(): bool
    {
        echo "m211101_000009_remove_old_tables_from_db cannot be reverted.\n";

        return false;
    }
}
