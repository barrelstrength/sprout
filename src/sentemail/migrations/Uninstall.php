<?php

namespace BarrelStrength\Sprout\sentemail\migrations;

use BarrelStrength\Sprout\sentemail\components\elements\SentEmailElement;
use BarrelStrength\Sprout\sentemail\db\SproutTable;
use BarrelStrength\Sprout\sentemail\SentEmailModule;
use Craft;
use craft\db\Migration;
use craft\db\Table;

class Uninstall extends Migration
{
    public function safeDown(): void
    {
        $moduleSettingsKey = SentEmailModule::projectConfigPath();
        $coreModuleSettingsKey = SentEmailModule::projectConfigPath('modules.' . SentEmailModule::class);

        $this->delete(Table::ELEMENTS, ['type' => SentEmailElement::class]);

        $this->dropTableIfExists(SproutTable::SENT_EMAILS);

        Craft::$app->getProjectConfig()->remove($moduleSettingsKey);
        Craft::$app->getProjectConfig()->remove($coreModuleSettingsKey);

        $this->delete(Table::USERPERMISSIONS, [
            'in', 'name', [
                SentEmailModule::p('accessModule', true),
                SentEmailModule::p('viewSentEmail', true),
                SentEmailModule::p('resendSentEmail', true),
            ],
        ]);
    }
}
