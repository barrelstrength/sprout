<?php

namespace BarrelStrength\Sprout\datastudio\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;

class m211101_000009_remove_old_tables_from_db extends Migration
{
    public const DATASETS_TABLE = '{{%sprout_datasets}}';

    public const OLD_DATASOURCES_TABLE = '{{%sproutreports_datasources}}';
    public const OLD_REPORTS_TABLE = '{{%sproutreports_reports}}';
    public const OLD_REPORTS_GROUPS_TABLE = '{{%sproutreports_reportgroups}}';

    public function safeUp(): void
    {
        $this->dropTableIfExists(self::OLD_REPORTS_TABLE);
        $this->dropTableIfExists(self::OLD_REPORTS_GROUPS_TABLE);
        $this->dropTableIfExists(self::OLD_DATASOURCES_TABLE);

        // Clean up old category reports
        $categoryReportIds = (new Query())
            ->select(['id'])
            ->from([self::DATASETS_TABLE])
            ->where([
                'type' => 'barrelstrength\sproutreportscategories\integrations\sproutreports\datasources\Categories',
            ])
            ->column();

        $this->delete(Table::ELEMENTS, [
            'in', 'id', $categoryReportIds
        ]);
    }

    public function safeDown(): bool
    {
        echo "m211101_000009_remove_old_tables_from_db cannot be reverted.\n";

        return false;
    }
}
