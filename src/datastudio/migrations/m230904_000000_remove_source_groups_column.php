<?php

namespace BarrelStrength\Sprout\datastudio\migrations;

use craft\db\Migration;
use craft\helpers\Db;

class m230904_000000_remove_source_groups_column extends Migration
{
    public const DATASETS_TABLE = '{{%sprout_datasets}}';

    public function safeUp(): void
    {
        if ($this->getDb()->tableExists(self::DATASETS_TABLE) && $this->getDb()->columnExists(self::DATASETS_TABLE, 'groupId')) {
            $fkName = Db::findForeignKey(self::DATASETS_TABLE, 'groupId');
            $this->dropForeignKey($fkName, self::DATASETS_TABLE);
            $this->dropColumn(self::DATASETS_TABLE, 'groupId');
        }
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
