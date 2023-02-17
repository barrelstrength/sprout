<?php

namespace BarrelStrength\Sprout\transactional\migrations;

use BarrelStrength\Sprout\transactional\components\elements\TransactionalEmailElement;
use BarrelStrength\Sprout\transactional\TransactionalModule;
use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;

class Uninstall extends Migration
{
    public function safeDown(): void
    {
        $moduleSettingsKey = TransactionalModule::projectConfigPath();
        $coreModuleSettingsKey = TransactionalModule::projectConfigPath('modules.' . TransactionalModule::class);

        $emailIds = (new Query())
            ->select('id')
            ->from([Table::ELEMENTS])
            ->where([
                'type' => TransactionalEmailElement::class,
            ])
            ->column();

        $this->delete(Table::ELEMENTS, ['in', 'id', $emailIds]);

        Craft::$app->getProjectConfig()->remove($moduleSettingsKey);
        Craft::$app->getProjectConfig()->remove($coreModuleSettingsKey);

        $this->delete(Table::USERPERMISSIONS, [
            'in', 'name', [
                TransactionalModule::p('accessModule', true),
                TransactionalModule::p('viewTransactionalEmail', true),
                TransactionalModule::p('editTransactionalEmail', true),
            ],
        ]);
    }
}
