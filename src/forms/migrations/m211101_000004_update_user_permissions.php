<?php

/** @noinspection DuplicatedCode */

namespace BarrelStrength\Sprout\forms\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;

class m211101_000004_update_user_permissions extends Migration
{
    public function safeUp(): void
    {
        $permissionMap = [
            'sprout-module-forms' => [
                'sproutForms-editForms' => 'sprout-module-forms:editForms',
                'sproutForms-viewEntries' => 'sprout-module-forms:viewSubmissions',
                'sproutForms-editEntries' => 'sprout-module-forms:editSubmissions',
            ],
        ];

        $permissions = (new Query())
            ->select(['id'])
            ->from([Table::USERPERMISSIONS])
            ->where([
                'like', 'name', 'sprout%', false,
            ])
            ->indexBy('name')
            ->column();

        foreach ($permissionMap as $moduleId => $permissionSet) {

            // Update Permission Names in db
            foreach ($permissionSet as $oldPermissionName => $newPermissionName) {
                $lowerCasePermissionName = strtolower($oldPermissionName);
                $permissionId = $permissions[$lowerCasePermissionName] ?? null;

                if (!$permissionId) {
                    continue;
                }

                // Update permission names one by one so we can also add accessModule permissions
                $this->update(Table::USERPERMISSIONS, [
                    'name' => strtolower($newPermissionName),
                ], ['id' => $permissionId], [], false);
            }

            // Add accessModule permissions
            $this->insert(Table::USERPERMISSIONS, [
                'name' => strtolower('sprout-module-forms:accessModule'),
            ]);
            $accessModulePermissionId = $this->db->getLastInsertID(Table::USERPERMISSIONS);

            $accessPluginPermissionId = (new Query())
                ->select(['id'])
                ->from([Table::USERPERMISSIONS])
                ->where([
                    'name' => 'accessplugin-sprout-forms',
                ])
                ->scalar();

            // Add accessModule permission to appropriate userpermissions_usergroups table
            // for any permissions applied to a group
            $accessPluginUserGroupIds = (new Query())
                ->select(['groupId'])
                ->from([Table::USERPERMISSIONS_USERGROUPS])
                ->where([
                    'permissionId' => $accessPluginPermissionId,
                ])
                ->column();

            // Assign the new permissions to the groups
            if (!empty($accessPluginUserGroupIds)) {
                $data = [];
                foreach ($accessPluginUserGroupIds as $groupId) {
                    $data[] = [$accessModulePermissionId, $groupId];
                }

                $this->batchInsert(Table::USERPERMISSIONS_USERGROUPS, ['permissionId', 'groupId'], $data);
            }

            // Add accessModule permission to appropriate userpermissions_users table
            // for any permissions applied to a user
            $accessPluginUserIds = (new Query())
                ->select(['userId'])
                ->from([Table::USERPERMISSIONS_USERS])
                ->where([
                    'permissionId' => $accessPluginPermissionId,
                ])
                ->column();

            // Assign the new permissions to the users
            if (!empty($accessPluginUserIds)) {
                $data = [];
                foreach ($accessPluginUserIds as $userId) {
                    $data[] = [$accessModulePermissionId, $userId];
                }

                $this->batchInsert(Table::USERPERMISSIONS_USERS, ['permissionId', 'userId'], $data);
            }

            // Remove access plugin permission that is no longer in use
            $this->delete(Table::USERPERMISSIONS, [
                'id' => $accessPluginPermissionId,
            ]);

            $this->delete(Table::USERPERMISSIONS, [
                'name' => 'editsproutformssettings',
            ]);
        }
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
