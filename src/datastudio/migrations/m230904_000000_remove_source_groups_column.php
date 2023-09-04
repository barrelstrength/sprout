<?php

namespace BarrelStrength\Sprout\datastudio\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

class m230904_000000_remove_source_groups_column extends Migration
{
    public const DATASETS_TABLE = '{{%sprout_datasets}}';

    public function safeUp(): void
    {
        $this->dropColumn(self::DATASETS_TABLE, 'groupId');
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
