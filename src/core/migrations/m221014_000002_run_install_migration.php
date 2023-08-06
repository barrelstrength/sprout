<?php

namespace BarrelStrength\Sprout\core\migrations;

use craft\db\Migration;

class m221014_000002_run_install_migration extends Migration
{
    public const SOURCE_GROUPS_TABLE = '{{%sprout_source_groups}}';

    public function safeUp(): void
    {
        if (!$this->getDb()->tableExists(self::SOURCE_GROUPS_TABLE)) {
            $this->createTable(self::SOURCE_GROUPS_TABLE, [
                'id' => $this->primaryKey(),
                'name' => $this->string()->notNull(),
                'type' => $this->string()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);

            $this->createIndex(null, self::SOURCE_GROUPS_TABLE, ['name']);
        }
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
