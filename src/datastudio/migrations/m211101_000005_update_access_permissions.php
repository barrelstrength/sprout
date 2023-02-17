<?php

namespace BarrelStrength\Sprout\datastudio\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;

class m211101_000005_update_access_permissions extends Migration
{
    public function safeUp(): void
    {
        // Add accessModule permissions
        $this->insert(Table::USERPERMISSIONS, [
            'name' => strtolower('sprout-module-data-studio:accessModule'),
        ]);
        $accessModulePermissionId = $this->db->getLastInsertID(Table::USERPERMISSIONS);

        $accessPluginPermissionId = (new Query())
            ->select(['id'])
            ->from([Table::USERPERMISSIONS])
            ->where([
                'name' => 'accessplugin-sprout-reports',
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
        echo "m211101_000005_update_user_permissions cannot be reverted.\n";

        return false;
    }
}
