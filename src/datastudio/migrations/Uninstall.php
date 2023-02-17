<?php

namespace BarrelStrength\Sprout\datastudio\migrations;

use BarrelStrength\Sprout\core\db\SproutTable as SproutTableCore;
use BarrelStrength\Sprout\datastudio\components\elements\DataSetElement;
use BarrelStrength\Sprout\datastudio\DataStudioModule;
use BarrelStrength\Sprout\datastudio\db\SproutTable;
use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;

class Uninstall extends Migration
{
    public function safeDown(): void
    {
        $moduleSettingsKey = DataStudioModule::projectConfigPath();
        $coreModuleSettingsKey = DataStudioModule::projectConfigPath('modules.' . DataStudioModule::class);

        $this->delete(Table::ELEMENTS, ['type' => DataSetElement::class]);

        $this->delete(SproutTableCore::SOURCE_GROUPS, [
            'type' => DataSetElement::class,
        ]);

        // Order matters
        $this->dropTableIfExists(SproutTable::DATASETS);

        Craft::$app->getProjectConfig()->remove($moduleSettingsKey);
        Craft::$app->getProjectConfig()->remove($coreModuleSettingsKey);

        $this->delete(Table::USERPERMISSIONS, [
            'name' => DataStudioModule::p('accessModule', true),
        ]);

        $dataStudioPermissionIds = (new Query())
            ->select('id')
            ->from(Table::USERPERMISSIONS)
            ->where([
                'or',
                ['like', 'name', DataStudioModule::p('viewReports%', true), false],
                ['like', 'name', DataStudioModule::p('editDataSet%', true), false],
            ])
            ->column();

        $this->delete(Table::USERPERMISSIONS, [
            'in', 'id', $dataStudioPermissionIds,
        ]);
    }
}
