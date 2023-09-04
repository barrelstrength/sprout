<?php

/** @noinspection DuplicatedCode */

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

        $newPermissionSlugs = [
            'viewReports',
            'editDataSet',
        ];

        $newViewReportsPermissionIds = [];
        $newEditDataSetPermissionIds = [];

        // Create new Permissions
        foreach ($newPermissionSlugs as $newPermissionSlug) {

            // Loop through Data Sources
            foreach ($components as $columns) {
                foreach ($columns as $types) {
                    foreach ($types as $type) {

                        $newType = $type['newType'];
                        $newPermissionName = "sprout-module-data-studio:$newPermissionSlug:$newType";

                        $this->insert(Table::USERPERMISSIONS, [
                            'name' => strtolower($newPermissionName),
                        ]);

                        if ($newPermissionSlug === 'viewReports') {
                            $newViewReportsPermissionIds[] = $this->db->getLastInsertID(Table::USERPERMISSIONS);
                        }

                        if ($newPermissionSlug === 'editDataSet') {
                            $newEditDataSetPermissionIds[] = $this->db->getLastInsertID(Table::USERPERMISSIONS);
                        }
                    }
                }
            }
        }

        $oldViewReportsPermissionIds = (new Query())
            ->select(['id'])
            ->from([Table::USERPERMISSIONS])
            ->where([
                'in', 'name', [
                    strtolower('sproutReports-viewReports'),
                    strtolower('sproutForms-viewReports'),
                ],
            ])
            ->column();

        $oldEditDataSetsPermissionIds = (new Query())
            ->select(['id'])
            ->from([Table::USERPERMISSIONS])
            ->where([
                'in', 'name', [
                    strtolower('sproutReports-editReports'),
                    strtolower('sproutForms-editReports'),
                ],
            ])
            ->column();

        // Gather User and User Groups who have the View Reports Permission
        $userPermissionWithViewReportsPermissionIds = (new Query())
            ->select(['userId'])
            ->from([Table::USERPERMISSIONS_USERS])
            ->where([
                'in', 'permissionId', $oldViewReportsPermissionIds,
            ])
            ->column();

        $userGroupPermissionsWithViewReportsPermissionIds = (new Query())
            ->select(['groupId'])
            ->from([Table::USERPERMISSIONS_USERGROUPS])
            ->where([
                'in', 'permissionId', $oldViewReportsPermissionIds,
            ])
            ->column();

        // Gather User and User Groups who have the View Reports Permission
        $userPermissionWithEditDataSetsPermissionIds = (new Query())
            ->select(['userId'])
            ->from([Table::USERPERMISSIONS_USERS])
            ->where([
                'in', 'permissionId', $oldEditDataSetsPermissionIds,
            ])
            ->column();

        $userGroupPermissionsWithEditDataSetsPermissionIds = (new Query())
            ->select(['groupId'])
            ->from([Table::USERPERMISSIONS_USERGROUPS])
            ->where([
                'in', 'permissionId', $oldEditDataSetsPermissionIds,
            ])
            ->column();

        // Delete references to Old Permissions
        $this->delete(Table::USERPERMISSIONS, [
            'in', 'id', $oldViewReportsPermissionIds,
        ]);

        $this->delete(Table::USERPERMISSIONS, [
            'in', 'id', $oldEditDataSetsPermissionIds,
        ]);

        $this->delete(Table::USERPERMISSIONS_USERS, [
            'in', 'permissionId', $oldViewReportsPermissionIds,
        ]);

        $this->delete(Table::USERPERMISSIONS_USERGROUPS, [
            'in', 'permissionId', $oldViewReportsPermissionIds,
        ]);

        $this->delete(Table::USERPERMISSIONS_USERS, [
            'in', 'permissionId', $oldEditDataSetsPermissionIds,
        ]);

        $this->delete(Table::USERPERMISSIONS_USERGROUPS, [
            'in', 'permissionId', $oldEditDataSetsPermissionIds,
        ]);

        // Removed. Use User Access permissions.
        $this->delete(Table::USERPERMISSIONS, [
            'name' => 'sproutreports-editdatasources',
        ]);

        // Removed. Use User Access permissions.
        $this->delete(Table::USERPERMISSIONS, [
            'name' => 'sproutreports-editsettings',
        ]);

        $userIdsWithViewReportsPermission = array_unique($userPermissionWithViewReportsPermissionIds);

        // Create User and User Group permission for new Permission ID
        foreach ($userIdsWithViewReportsPermission as $userId) {
            foreach ($newViewReportsPermissionIds as $newViewReportsPermissionId) {
                $this->insert(Table::USERPERMISSIONS_USERS, [
                    'userId' => $userId,
                    'permissionId' => $newViewReportsPermissionId,
                ]);
            }
        }

        $groupIdsWithViewReportsPermission = array_unique($userGroupPermissionsWithViewReportsPermissionIds);

        foreach ($groupIdsWithViewReportsPermission as $groupId) {
            foreach ($newViewReportsPermissionIds as $newViewReportsPermissionId) {
                $this->insert(Table::USERPERMISSIONS_USERGROUPS, [
                    'groupId' => $groupId,
                    'permissionId' => $newViewReportsPermissionId,
                ]);
            }
        }

        $userIdsWithEditDataSetsPermission = array_unique($userPermissionWithEditDataSetsPermissionIds);

        // Create User and User Group permission for new Permission ID
        foreach ($userIdsWithEditDataSetsPermission as $userId) {
            foreach ($newEditDataSetPermissionIds as $newPermissionId) {
                $this->insert(Table::USERPERMISSIONS_USERS, [
                    'userId' => $userId,
                    'permissionId' => $newPermissionId,
                ]);
            }
        }

        $groupIdsWithEditDataSetsPermission = array_unique($userGroupPermissionsWithEditDataSetsPermissionIds);

        foreach ($groupIdsWithEditDataSetsPermission as $groupId) {
            foreach ($newEditDataSetPermissionIds as $newPermissionId) {
                $this->insert(Table::USERPERMISSIONS_USERGROUPS, [
                    'groupId' => $groupId,
                    'permissionId' => $newPermissionId,
                ]);
            }
        }
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
