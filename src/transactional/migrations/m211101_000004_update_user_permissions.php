<?php

/** @noinspection DuplicatedCode */

namespace BarrelStrength\Sprout\transactional\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;

class m211101_000004_update_user_permissions extends Migration
{
    public function safeUp(): void
    {
        $this->updateNotificationsPermissions();

        // Add accessModule permissions
        $this->insert(Table::USERPERMISSIONS, [
            'name' => strtolower('sprout-module-transactional:accessModule'),
        ]);
        $accessModulePermissionId = $this->db->getLastInsertID(Table::USERPERMISSIONS);

        $accessPluginPermissionId = (new Query())
            ->select(['id'])
            ->from([Table::USERPERMISSIONS])
            ->where([
                'name' => 'accessplugin-sprout-email',
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
            'name' => 'editsproutemailsettings',
        ]);
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }

    public function updateNotificationsPermissions(): void
    {
        // Create the new Permission
        $this->insert(Table::USERPERMISSIONS, [
            'name' => strtolower('sprout-module-transactional:viewTransactionalEmail'),
        ]);
        $newViewEmailPermissionId = $this->db->getLastInsertID(Table::USERPERMISSIONS);

        $this->insert(Table::USERPERMISSIONS, [
            'name' => strtolower('sprout-module-transactional:editTransactionalEmail'),
        ]);
        $newEditEmailPermissionId = $this->db->getLastInsertID(Table::USERPERMISSIONS);

        $oldViewEmailPermissionIds = (new Query())
            ->select(['id'])
            ->from([Table::USERPERMISSIONS])
            ->where([
                'in', 'name', [
                    strtolower('sproutEmail-viewNotifications'),
                    strtolower('sproutForms-viewNotifications'),
                ],
            ])
            ->column();

        $oldEditEmailPermissionIds = (new Query())
            ->select(['id'])
            ->from([Table::USERPERMISSIONS])
            ->where([
                'in', 'name', [
                    strtolower('sproutEmail-editNotifications'),
                    strtolower('sproutForms-editNotifications'),
                ],
            ])
            ->column();

        // Gather User and User Groups who have the View Reports Permission
        $userPermissionWithViewEmailPermissionIds = (new Query())
            ->select(['userId'])
            ->from([Table::USERPERMISSIONS_USERS])
            ->where([
                'in', 'permissionId', $oldViewEmailPermissionIds,
            ])
            ->column();

        $userGroupPermissionsWithViewEmailPermissionIds = (new Query())
            ->select(['groupId'])
            ->from([Table::USERPERMISSIONS_USERGROUPS])
            ->where([
                'in', 'permissionId', $oldViewEmailPermissionIds,
            ])
            ->column();

        // Gather User and User Groups who have the View Reports Permission
        $userPermissionWithEditEmailPermissionIds = (new Query())
            ->select(['userId'])
            ->from([Table::USERPERMISSIONS_USERS])
            ->where([
                'in', 'permissionId', $oldEditEmailPermissionIds,
            ])
            ->column();

        $userGroupPermissionsWithEditEmailPermissionIds = (new Query())
            ->select(['groupId'])
            ->from([Table::USERPERMISSIONS_USERGROUPS])
            ->where([
                'in', 'permissionId', $oldEditEmailPermissionIds,
            ])
            ->column();

        // Delete references to Old Permissions
        $this->delete(Table::USERPERMISSIONS, [
            'in', 'id', $oldViewEmailPermissionIds,
        ]);

        $this->delete(Table::USERPERMISSIONS, [
            'in', 'id', $oldEditEmailPermissionIds,
        ]);

        $this->delete(Table::USERPERMISSIONS_USERS, [
            'in', 'permissionId', $oldViewEmailPermissionIds,
        ]);

        $this->delete(Table::USERPERMISSIONS_USERGROUPS, [
            'in', 'permissionId', $oldViewEmailPermissionIds,
        ]);

        $this->delete(Table::USERPERMISSIONS_USERS, [
            'in', 'permissionId', $oldEditEmailPermissionIds,
        ]);

        $this->delete(Table::USERPERMISSIONS_USERGROUPS, [
            'in', 'permissionId', $oldEditEmailPermissionIds,
        ]);

        $userIdsWithViewEmailPermission = array_unique($userPermissionWithViewEmailPermissionIds);

        // Create User and User Group permission for new Permission ID
        foreach ($userIdsWithViewEmailPermission as $userId) {
            $this->insert(Table::USERPERMISSIONS_USERS, [
                'userId' => $userId,
                'permissionId' => $newViewEmailPermissionId,
            ]);
        }

        $groupIdsWithViewEmailPermission = array_unique($userGroupPermissionsWithViewEmailPermissionIds);

        foreach ($groupIdsWithViewEmailPermission as $groupId) {
            $this->insert(Table::USERPERMISSIONS_USERGROUPS, [
                'groupId' => $groupId,
                'permissionId' => $newViewEmailPermissionId,
            ]);
        }

        $userIdsWithEditEmailPermission = array_unique($userPermissionWithEditEmailPermissionIds);

        // Create User and User Group permission for new Permission ID
        foreach ($userIdsWithEditEmailPermission as $userId) {
            $this->insert(Table::USERPERMISSIONS_USERS, [
                'userId' => $userId,
                'permissionId' => $newEditEmailPermissionId,
            ]);
        }

        $groupIdsWithEditEmailPermission = array_unique($userGroupPermissionsWithEditEmailPermissionIds);

        foreach ($groupIdsWithEditEmailPermission as $groupId) {
            $this->insert(Table::USERPERMISSIONS_USERGROUPS, [
                'groupId' => $groupId,
                'permissionId' => $newEditEmailPermissionId,
            ]);
        }
    }
}
