<?php

namespace BarrelStrength\Sprout\meta\migrations;

use BarrelStrength\Sprout\meta\migrations\helpers\InsertDefaultMetadata;
use Craft;
use craft\db\Migration;
use craft\db\Table;

class m211101_000000_run_install_migration extends Migration
{
    public const SPROUT_KEY = 'sprout';
    public const MODULES_KEY = self::SPROUT_KEY . '.sprout-module-core.modules';
    public const MODULE_ID = 'sprout-module-meta';
    public const MODULE_CLASS = 'BarrelStrength\Sprout\meta\MetaModule';
    public const SETTING_METADATA_VARIABLE = 'metadata';
    public const GLOBAL_METADATA_TABLE = '{{%sprout_global_metadata}}';
    public const OLD_GLOBALS_TABLE = '{{%sproutseo_globals}}';

    public function safeUp(): void
    {
        $moduleSettingsKey = self::SPROUT_KEY . '.' . self::MODULE_ID;
        $coreModuleSettingsKey = self::MODULES_KEY . '.' . self::MODULE_CLASS;

        $this->createTables();

        if ($this->isNewInstallation()) {
            $defaultMetadataMigration = new InsertDefaultMetadata();
            ob_start();
            $defaultMetadataMigration->safeUp();
            ob_end_clean();
        }

        Craft::$app->getProjectConfig()->set($moduleSettingsKey, [
            'enableRenderMetadata' => true,
            'maxMetaDescriptionLength' => 160,
        ], "Update Sprout CP Settings for “{$moduleSettingsKey}”");

        Craft::$app->getProjectConfig()->set($coreModuleSettingsKey, [
            'enabled' => true,
        ]);
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }

    public function createTables(): void
    {
        if (!$this->db->tableExists(self::GLOBAL_METADATA_TABLE)) {
            $this->createTable(self::GLOBAL_METADATA_TABLE, [
                'id' => $this->primaryKey(),
                'siteId' => $this->integer()->notNull(),
                'identity' => $this->text(),
                'ownership' => $this->text(),
                'contacts' => $this->text(),
                'social' => $this->text(),
                'robots' => $this->text(),
                'settings' => $this->text(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);

            $this->createIndex(null, self::GLOBAL_METADATA_TABLE, ['siteId']);

            $this->addForeignKey(null, self::GLOBAL_METADATA_TABLE, ['siteId'], Table::SITES, ['id'], 'CASCADE', 'CASCADE');
        }
    }

    /**
     * Returns true if install migration is being run as part of a fresh install
     *
     * Checks if the old, shared datasources table exists to determine if this is an upgrade migration
     */
    public function isNewInstallation(): bool
    {
        return !Craft::$app->getDb()->tableExists(self::OLD_GLOBALS_TABLE);
    }
}
