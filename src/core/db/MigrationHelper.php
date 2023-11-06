<?php

namespace BarrelStrength\Sprout\core\db;

use BarrelStrength\Sprout\core\migrations\Uninstall;
use BarrelStrength\Sprout\core\modules\SproutModuleTrait;
use BarrelStrength\Sprout\core\Sprout;
use BarrelStrength\Sprout\core\SproutSettings;
use Craft;
use craft\db\MigrationManager;
use craft\db\Query;
use craft\db\Table;
use craft\errors\MigrationException;
use yii\db\Migration;

class MigrationHelper
{
    /**
     * Runs all included Sprout module migrations for a given Sprout plugin.
     *
     * This method is called from a Sprout Plugins. Module migrations
     * do not have an 'Install' migration and instead just run all
     * migration classes on install or update. This helps us avoid
     * needing to update logic in the Install migration if anything
     * changes in the future. Updates will always be handled with new
     * migration files and any changes to schema can be dealt with
     * in the Uninstall migration.
     *
     * Logic derived from:
     * - craft\base\Plugin::createInstallMigration()
     * - craft\base\Plugin::install()
     */
    public static function runMigrations(SproutPluginMigrationInterface $plugin): void
    {
        // Make sure the Sprout Module comes first
        $modules = [Sprout::class, ...$plugin::getSchemaDependencies()];

        foreach ($modules as $moduleClass) {
            if (!$moduleClass::hasMigrations()) {
                continue;
            }

            // Make sure all migrations have run.
            // Installation requirements will be handled by migration classes.
            $migrator = $moduleClass::getInstance()->getMigrator();
            $migrator->up();
        }
    }

    /**
     * Runs Uninstall migrations for any Sprout module that is no longer needed
     * by an installed Sprout plugin. Data and database tables are only removed
     * once the Uninstall condition is triggered.
     *
     * The Uninstall migration can use the service layer directly. Unlike the
     * Install migration, the Uninstall migration can be updated to reference
     * changes in schema without creating additional migration problems.
     */
    public static function runUninstallMigrations(SproutPluginMigrationInterface $plugin): void
    {
        $installedPlugins = (new Query())
            ->select('handle')
            ->from(Table::PLUGINS)
            ->column();

        $requiredModules = [];
        $installedSproutPlugins = [];

        foreach (Sprout::PLUGINS as $handle => $class) {
            if (in_array($handle, $installedPlugins, true)) {
                $installedSproutPlugins[] = $class;
            }
        }

        foreach ($installedSproutPlugins as $class) {
            $interfaces = class_implements($class);

            // Only check Sprout plugins with schema
            if (!in_array(SproutPluginMigrationInterface::class, $interfaces, true)) {
                continue;
            }

            // Skip the current plugin being uninstalled
            if ($plugin::class === $class) {
                continue;
            }

            // Add the modules of all other Sprout plugins to the keepers list
            foreach ($class::getSchemaDependencies() as $moduleClass) {
                $requiredModules[] = $moduleClass;
            }
        }

        $modulesSafeToUninstall = array_diff($plugin::getSchemaDependencies(), $requiredModules);

        /** @var SproutModuleTrait $moduleClass */
        foreach ($modulesSafeToUninstall as $moduleClass) {
            if (!$moduleClass::hasMigrations()) {
                continue;
            }

            /** @var MigrationManager $migrator */
            $migrator = $moduleClass::getInstance()->getMigrator();

            if (($migration = self::createUninstallMigration($migrator)) !== null) {
                try {
                    $migrator->migrateDown($migration);
                } catch (MigrationException $e) {
                    throw new MigrationException($migration, null, $e->getMessage());
                }
            }

            // Remove track from migrations table
            $migrator->truncateHistory();

            // Remove module settings from project config
            Craft::$app->getProjectConfig()->remove($moduleClass::projectConfigPath());
        }

        if (count($installedSproutPlugins) <= 1) {
            // Remove all settings if this is the last Sprout plugin
            Craft::$app->getProjectConfig()->remove(SproutSettings::ROOT_PROJECT_CONFIG_KEY);

            // Remove Sprout Core
            // @var Migrator $coreUninstall
            $coreUninstall = new Uninstall();
            $coreUninstall->safeDown();

            /** @var MigrationManager $migrator */
            $coreMigrator = Sprout::getInstance()->getMigrator();
            $coreMigrator->truncateHistory();
        }
    }

    /**
     * Creates an uninstall migration instance for a given Sprout module.
     */
    public static function createUninstallMigration($migrator): ?Migration
    {
        $path = $migrator->migrationPath . DIRECTORY_SEPARATOR . 'Uninstall.php';

        // Run the install migration, if there is one, or continue
        if (!is_file($path)) {
            return null;
        }

        require_once $path;
        $class = $migrator->migrationNamespace . '\\Uninstall';

        return new $class();
    }
}
