<?php

namespace BarrelStrength\Sprout\forms\migrations\helpers;

use craft\db\Migration;
use craft\db\Table;

class CreateFormContentTable extends Migration
{
    public ?string $tableName = null;

    public function safeUp(): void
    {
        $this->createTable($this->tableName, [
            'id' => $this->primaryKey(),
            'elementId' => $this->integer()->notNull(),
            'siteId' => $this->integer()->notNull(),
            'title' => $this->string(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createIndex(null, $this->tableName, ['elementId', 'siteId'], true);

        $this->addForeignKey(null, $this->tableName, ['elementId'], Table::ELEMENTS, ['id'], 'CASCADE');
        $this->addForeignKey(null, $this->tableName, ['siteId'], Table::SITES, ['id'], 'CASCADE', 'CASCADE');
    }

    public function safeDown(): bool
    {
        return false;
    }
}
