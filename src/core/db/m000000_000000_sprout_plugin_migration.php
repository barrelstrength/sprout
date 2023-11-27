<?php

namespace BarrelStrength\Sprout\core\db;

use craft\db\Migration;
use craft\helpers\Db;

/**
 * Extend this class when creating a Sprout plugin migration
 * to ensure the plugin triggers all Sprout module migration tracks
 * in the proper order.
 */
abstract class m000000_000000_sprout_plugin_migration extends Migration
{
    /**
     * Each plugin migration needs to tell us what plugin instance to act on.
     */
    abstract public function getPluginInstance(): SproutPluginMigrationInterface;

    public function safeUp(): void
    {
        $plugin = $this->getPluginInstance();

        $migrator = $plugin->getMigrator();
        $migrator->runParentMigrations = false;

        $migrator->up();
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
