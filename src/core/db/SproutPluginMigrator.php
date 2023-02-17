<?php

namespace BarrelStrength\Sprout\core\db;

use BarrelStrength\Sprout\core\modules\SproutModuleTrait;
use Craft;
use craft\base\Plugin;
use craft\db\MigrationManager;
use ReflectionClass;

class SproutPluginMigrator extends MigrationManager
{
    /**
     * Adds the schemaDependencies property to the MigrationManager class
     *
     * @var SproutModuleTrait[]|MigrationTrait[]
     */
    public array $schemaDependencies = [];

    /**
     * Create a MigrationManager instance for a given Sprout Plugin.
     *
     * Note: This migrator will never need to run any migrations for the
     * plugin itself but is necessary to run Sprout module migrations
     */
    public static function make(Plugin $plugin): SproutPluginMigrator
    {
        $ref = new ReflectionClass($plugin);
        $ns = $ref->getNamespaceName();

        /** @var SproutPluginMigrator $migrator */
        $migrator = Craft::createObject([
            'class' => self::class,
            'track' => "plugin:$plugin->id",
            'migrationNamespace' => ($ns ? $ns . '\\' : '') . 'migrations',
            'migrationPath' => $plugin->getBasePath() . DIRECTORY_SEPARATOR . 'migrations',
            'schemaDependencies' => $plugin::getSchemaDependencies(),
        ]);

        return $migrator;
    }

    /**
     * Adds support for running all Sprout module migrations
     */
    public function up(int $limit = 0): void
    {
        // Run an migrations found in the plugin, as usual
        parent::up();

        // Loop through Sprout modules
        foreach ($this->schemaDependencies as $moduleClass) {
            if (!$moduleClass::hasMigrations()) {
                continue;
            }

            ob_start();
            $migrator = $moduleClass::getInstance()->getMigrator();
            $migrator->up();
            ob_end_clean();
        }
    }
}
