<?php

namespace BarrelStrength\Sprout\sitemaps\migrations;

use BarrelStrength\Sprout\sitemaps\db\SproutTable;
use BarrelStrength\Sprout\sitemaps\SitemapsModule;
use Craft;
use craft\db\Migration;
use craft\db\Table;

class Uninstall extends Migration
{
    public function safeDown(): void
    {
        $moduleSettingsKey = SitemapsModule::projectConfigPath();
        $coreModuleSettingsKey = SitemapsModule::projectConfigPath('modules.' . SitemapsModule::class);

        $this->dropTableIfExists(SproutTable::SITEMAPS);

        Craft::$app->getProjectConfig()->remove($moduleSettingsKey);
        Craft::$app->getProjectConfig()->remove($coreModuleSettingsKey);

        $this->delete(Table::USERPERMISSIONS, [
            'in', 'name', [
                SitemapsModule::p('accessModule', true),
                SitemapsModule::p('editSitemaps', true),
            ],
        ]);
    }
}
