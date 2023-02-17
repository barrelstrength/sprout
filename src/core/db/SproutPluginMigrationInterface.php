<?php

namespace BarrelStrength\Sprout\core\db;

use craft\db\MigrationManager;

/**
 * Sprout Plugins using modules with schema must implement this interface
 */
interface SproutPluginMigrationInterface
{
    /**
     * The class names of the Sprout modules with schema in which this plugin depends on
     *
     * @example
     *
     * return [
     *   FormsModule::class,
     *   DataStudioModule::class,
     * ];
     */
    public static function getSchemaDependencies(): array;

    /**
     * Sprout Plugins using modules with schema must override the default
     * migrator method and return a SproutPluginMigrator class
     *
     * @return SproutPluginMigrator|MigrationManager
     */
    public function getMigrator(): SproutPluginMigrator|MigrationManager;
}

