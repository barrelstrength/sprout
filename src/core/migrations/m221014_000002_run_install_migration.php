<?php

namespace BarrelStrength\Sprout\core\migrations;

use craft\db\Migration;

class m221014_000002_run_install_migration extends Migration
{
    public const SETTINGS_TABLE = '{{%sprout_settings}}';

    public function safeUp(): void
    {
        if (!$this->getDb()->tableExists(self::SETTINGS_TABLE)) {
            $this->createTable(self::SETTINGS_TABLE, [
                'id' => $this->primaryKey(),
                'siteId' => $this->integer()->notNull(),
                'moduleId' => $this->string()->notNull(),
                'name' => $this->string()->notNull(),
                'settings' => $this->text()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
