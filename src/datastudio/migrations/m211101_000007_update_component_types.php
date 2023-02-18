<?php

namespace BarrelStrength\Sprout\datastudio\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;

class m211101_000007_update_component_types extends Migration
{
    public const DATASETS_TABLE = '{{%sprout_datasets}}';

    public function safeUp(): void
    {
        $components = [
            'sproutreports_datasources' => [
                'type' => [
                    // Reports
                    [
                        'oldType' => 'barrelstrength\sproutbasereports\datasources\CustomQuery',
                        'newType' => 'BarrelStrength\Sprout\datastudio\components\datasources\CustomQueryDataSource',
                    ],
                    [
                        'oldType' => 'barrelstrength\sproutbasereports\datasources\CustomTwigTemplate',
                        'newType' => 'BarrelStrength\Sprout\datastudio\components\datasources\CustomTwigTemplateQueryDataSource',
                    ],
                    [
                        'oldType' => 'barrelstrength\sproutbasereports\datasources\Users',
                        'newType' => 'BarrelStrength\Sprout\datastudio\components\datasources\UsersDataSource',
                    ],

                    // Forms
                    [
                        'oldType' => 'barrelstrength\sproutforms\integrations\sproutreports\datasources\EntriesDataSource',
                        'newType' => 'BarrelStrength\Sprout\forms\components\datasources\SubmissionsDataSource',
                    ],
                    [
                        'oldType' => 'barrelstrength\sproutforms\integrations\sproutreports\datasources\IntegrationLogDataSource',
                        'newType' => 'BarrelStrength\Sprout\forms\components\datasources\IntegrationLogDataSource',
                    ],
                    [
                        'oldType' => 'barrelstrength\sproutforms\integrations\sproutreports\datasources\SpamLogDataSource',
                        'newType' => 'BarrelStrength\Sprout\forms\components\datasources\SpamLogDataSource',
                    ],

                    // Commerce
                    [
                        'oldType' => 'barrelstrength\sproutreportscommerce\integrations\sproutreports\datasources\CommerceProductRevenueDataSource',
                        'newType' => 'BarrelStrength\Sprout\datastudio\components\datasources\CommerceProductRevenueDataSource',
                    ],
                    [
                        'oldType' => 'barrelstrength\sproutreportscommerce\integrations\sproutreports\datasources\CommerceOrderHistoryDataSource',
                        'newType' => 'BarrelStrength\Sprout\datastudio\components\datasources\CommerceOrderHistoryDataSource',
                    ],
                ],
            ],
        ];

        foreach ($components as $dbTableName => $columns) {
            foreach ($columns as $column => $types) {
                foreach ($types as $type) {

                    $dbTable = '{{%' . $dbTableName . '}}';

                    if (!$this->db->columnExists($dbTable, $column)) {
                        continue;
                    }

                    $this->update($dbTable, [
                        $column => $type['newType'],
                    ], [
                        $column => $type['oldType'],
                    ], [], false);
                }
            }
        }

        $permissionMap = [
            'sproutReports-viewReports' => 'viewReports',
            'sproutReports-editReports' => 'editDataSet',
            'sproutForms-viewReports' => 'viewReports',
            'sproutForms-editReports' => 'editDataSet',
        ];

        $permissions = (new Query())
            ->select([
                'id',
            ])
            ->from([Table::USERPERMISSIONS])
            ->where([
                'like', 'name', 'sprout%', false,
            ])
            ->indexBy('name')
            ->column();

        // Update Permission Names in db
        foreach ($permissionMap as $oldPermissionName => $newPermissionSlug) {

            $lowerCasePermissionName = strtolower($oldPermissionName);
            $permissionId = $permissions[$lowerCasePermissionName] ?? null;

            if (!$permissionId) {
                continue;
            }

            // Loop through Data Sources and
            // - add view/edit permissions
            foreach ($components as $columns) {
                foreach ($columns as $types) {
                    foreach ($types as $type) {

                        $newType = $type['newType'];
                        $newPermissionName = "sprout-module-data-studio:$newType:$newPermissionSlug";

                        $this->update(Table::USERPERMISSIONS, [
                            'name' => strtolower($newPermissionName),
                        ], ['id' => $permissionId], [], false);
                    }
                }
            }
        }

        // Removed. Use User Access permissions.
        $this->delete(Table::USERPERMISSIONS, [
            'name' => 'sproutreports-editdatasources',
        ]);

        // Removed. Use User Access permissions.
        $this->delete(Table::USERPERMISSIONS, [
            'name' => 'sproutreports-editsettings',
        ]);
    }

    public function safeDown(): bool
    {
        echo "m211101_000007_update_component_types cannot be reverted.\n";

        return false;
    }
}
