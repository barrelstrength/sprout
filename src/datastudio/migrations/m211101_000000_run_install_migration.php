<?php

namespace BarrelStrength\Sprout\datastudio\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;

class m211101_000000_run_install_migration extends Migration
{
    public const SPROUT_KEY = 'sprout';
    public const MODULE_ID = 'sprout-module-data-studio';
    public const MODULE_CLASS = 'BarrelStrength\Sprout\datastudio\DataStudioModule';

    public const DATASETS_TABLE = '{{%sprout_datasets}}';
    public const SOURCE_GROUPS_TABLE = '{{%sprout_source_groups}}';

    public function safeUp(): void
    {
        $moduleSettingsKey = self::SPROUT_KEY . '.' . self::MODULE_ID;
        $coreModuleSettingsKey = $moduleSettingsKey . '.modules.' . self::MODULE_CLASS;

        $this->createTables();

        Craft::$app->getProjectConfig()->set($moduleSettingsKey, [
            'defaultPageLength' => 10,
            'defaultExportDelimiter' => ',',
        ], "Update Sprout CP Settings for “{$moduleSettingsKey}”");

        Craft::$app->getProjectConfig()->set($coreModuleSettingsKey, [
            'alternateName' => '',
            'enabled' => true,
        ]);
    }

    public function safeDown(): bool
    {
        echo "m211101_000000_run_install_migration cannot be reverted.\n";

        return false;
    }

    public function createTables(): void
    {
        if (!$this->getDb()->tableExists(self::DATASETS_TABLE)) {
            $this->createTable(self::DATASETS_TABLE, [
                'id' => $this->primaryKey(),
                'groupId' => $this->integer(),
                'name' => $this->string(),
                'handle' => $this->string(),
                'description' => $this->text(),
                'nameFormat' => $this->string(),
                'allowHtml' => $this->boolean()->notNull()->defaultValue(false),
                'type' => $this->string()->notNull(),
                'sortOrder' => $this->string(),
                'sortColumn' => $this->string(),
                'delimiter' => $this->string(),
                'visualizationType' => $this->string(),
                'visualizationSettings' => $this->text(),
                'settings' => $this->text(),
                'enabled' => $this->boolean()->notNull()->defaultValue(true),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);

            $this->createIndex(null, self::DATASETS_TABLE, ['groupId']);
            $this->createIndex(null, self::DATASETS_TABLE, ['name']);
            $this->createIndex(null, self::DATASETS_TABLE, ['handle']);
            $this->createIndex(null, self::DATASETS_TABLE, ['type']);

            $this->addForeignKey(null, self::DATASETS_TABLE, ['id'], Table::ELEMENTS, ['id'], 'CASCADE', 'CASCADE');
            $this->addForeignKey(null, self::DATASETS_TABLE, ['groupId'], self::SOURCE_GROUPS_TABLE, ['id'], 'SET NULL');
        }
    }
}
