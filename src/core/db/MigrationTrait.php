<?php

namespace BarrelStrength\Sprout\core\db;

use Craft;
use craft\console\controllers\MigrateController;
use craft\db\MigrationManager;
use craft\events\RegisterMigratorEvent;
use yii\base\Event;

/**
 * Add MigrationTrait to any Sprout module that needs to support migrations
 *
 * MigrationTrait echoes behavior from craft\base\Plugin and gives Sprout modules
 * the ability to be managed by their own migration tracks.
 *
 * Check SproutModuleTrait::hasMigrations() before calling MigrationTrait::getMigrator()
 * whenever looping through all modules and running migrations.
 */
trait MigrationTrait
{
    public function getMigrator(): MigrationManager
    {
        $migrationNamespace = 'BarrelStrength\\Sprout\\' . static::getShortNameSlug() . '\\migrations';
        $migrationBasePath = Craft::getAlias('@BarrelStrength/Sprout' . DIRECTORY_SEPARATOR . static::getShortNameSlug());
        $migrationPath = $migrationBasePath . DIRECTORY_SEPARATOR . 'migrations';

        /** @var MigrationManager $migrator */
        $migrator = Craft::createObject([
            'class' => MigrationManager::class,
            'track' => $this->id,
            'migrationNamespace' => $migrationNamespace,
            'migrationPath' => $migrationPath,
        ]);

        return $migrator;
    }
}
