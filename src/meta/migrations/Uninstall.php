<?php

namespace BarrelStrength\Sprout\meta\migrations;

use BarrelStrength\Sprout\meta\components\fields\ElementMetadataField;
use BarrelStrength\Sprout\meta\db\SproutTable;
use BarrelStrength\Sprout\meta\MetaModule;
use Craft;
use craft\db\Migration;
use craft\db\Table;

class Uninstall extends Migration
{
    public function safeDown(): void
    {
        $moduleSettingsKey = MetaModule::projectConfigPath();
        $coreModuleSettingsKey = MetaModule::projectConfigPath('modules.' . MetaModule::class);

        // Delete Tables
        $this->dropTableIfExists(SproutTable::GLOBAL_METADATA);

        // Delete Fields
        $this->delete(Table::FIELDS, ['type' => ElementMetadataField::class]);

        Craft::$app->getProjectConfig()->remove($moduleSettingsKey);
        Craft::$app->getProjectConfig()->remove($coreModuleSettingsKey);

        $this->delete(Table::USERPERMISSIONS, [
            'in', 'name', [
                MetaModule::p('accessModule', true),
                MetaModule::p('editGlobals', true),
            ],
        ]);
    }
}
