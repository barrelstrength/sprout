<?php

/** @noinspection DuplicatedCode DuplicatedCode */

namespace BarrelStrength\Sprout\redirects\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;

class m211101_000004_update_user_permissions extends Migration
{
    public function safeUp(): void
    {
        $this->updateEditRedirectPermissions();

        // Add accessModule permissions
        $this->insert(Table::USERPERMISSIONS, [
            'name' => strtolower('sprout-module-redirects:accessModule'),
        ]);
        $accessModulePermissionId = $this->db->getLastInsertID(Table::USERPERMISSIONS);

        $accessPluginPermissionId = (new Query())
            ->select(['id'])
            ->from([Table::USERPERMISSIONS])
            ->where([
                'name' => 'accessplugin-sprout-redirects',
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
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }

    public function updateEditRedirectPermissions(): void
    {
        // Create the new Permission
        $this->insert(Table::USERPERMISSIONS, [
            'name' => strtolower('sprout-module-redirects:editRedirects'),
        ]);
        $newEditRedirectsPermissionId = $this->db->getLastInsertID(Table::USERPERMISSIONS);

        $oldEditRedirectPermissionIds = (new Query())
            ->select(['id'])
            ->from([Table::USERPERMISSIONS])
            ->where([
                'in', 'name', [
                    strtolower('sproutRedirects-editRedirects'),
                    strtolower('sproutSeo-editRedirects'),
                ],
            ])
            ->column();

        // Gather User and User Groups who have the Permission
        $userPermissionWithEditRedirectPermissionIds = (new Query())
            ->select(['userId'])
            ->from([Table::USERPERMISSIONS_USERS])
            ->where([
                'in', 'permissionId', $oldEditRedirectPermissionIds,
            ])
            ->column();

        $userGroupPermissionsWithEditRedirectPermissionIds = (new Query())
            ->select(['groupId'])
            ->from([Table::USERPERMISSIONS_USERGROUPS])
            ->where([
                'in', 'permissionId', $oldEditRedirectPermissionIds,
            ])
            ->column();

        // Delete references to Old Permissions
        $this->delete(Table::USERPERMISSIONS, [
            'in', 'id', $oldEditRedirectPermissionIds,
        ]);

        $this->delete(Table::USERPERMISSIONS_USERS, [
            'in', 'permissionId', $oldEditRedirectPermissionIds,
        ]);

        $this->delete(Table::USERPERMISSIONS_USERGROUPS, [
            'in', 'permissionId', $oldEditRedirectPermissionIds,
        ]);

        $userIdsWithPermission = array_unique($userPermissionWithEditRedirectPermissionIds);

        // Create User and User Group permission for new Permission ID
        foreach ($userIdsWithPermission as $userId) {
            $this->insert(Table::USERPERMISSIONS_USERS, [
                'userId' => $userId,
                'permissionId' => $newEditRedirectsPermissionId,
            ]);
        }

        $groupIdsWithPermission = array_unique($userGroupPermissionsWithEditRedirectPermissionIds);

        foreach ($groupIdsWithPermission as $groupId) {
            $this->insert(Table::USERPERMISSIONS_USERGROUPS, [
                'groupId' => $groupId,
                'permissionId' => $newEditRedirectsPermissionId,
            ]);
        }
    }
}
