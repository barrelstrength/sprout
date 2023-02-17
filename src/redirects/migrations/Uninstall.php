<?php

namespace BarrelStrength\Sprout\redirects\migrations;

use BarrelStrength\Sprout\redirects\components\elements\RedirectElement;
use BarrelStrength\Sprout\redirects\db\SproutTable;
use BarrelStrength\Sprout\redirects\RedirectsModule;
use Craft;
use craft\db\Migration;
use craft\db\Table;

class Uninstall extends Migration
{
    public function safeDown(): void
    {
        $moduleSettingsKey = RedirectsModule::projectConfigPath();
        $coreModuleSettingsKey = RedirectsModule::projectConfigPath('modules.' . RedirectsModule::class);

        $this->delete(Table::ELEMENTS, ['type' => RedirectElement::class]);

        $this->dropTableIfExists(SproutTable::REDIRECTS);

        Craft::$app->getProjectConfig()->remove($moduleSettingsKey);
        Craft::$app->getProjectConfig()->remove($coreModuleSettingsKey);

        $this->delete(Table::USERPERMISSIONS, [
            'in', 'name', [
                RedirectsModule::p('accessModule', true),
                RedirectsModule::p('editRedirects', true),
            ],
        ]);
    }
}
