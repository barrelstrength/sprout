<?php
/** @noinspection DuplicatedCode DuplicatedCode */

namespace BarrelStrength\Sprout\sentemail\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;

class m240223_000001_cleanup_resend_permission extends Migration
{
    public function safeUp(): void
    {
        $oldResendSentEmailPermissionId = (new Query())
            ->select('id')
            ->from(Table::USERPERMISSIONS)
            ->where(['name' => strtolower('sprout-module-sent-email:resendEmails')])
            ->scalar();

        $resendSentEmailPermissionId = (new Query())
            ->select('id')
            ->from(Table::USERPERMISSIONS)
            ->where(['name' => strtolower('sprout-module-sent-email:resendSentEmail')])
            ->scalar();

        // if the old permission still exists, clean it up
        if ($oldResendSentEmailPermissionId) {
            if (!$resendSentEmailPermissionId) {
                // if the correct permission does not yet exist
                // update the old permission to the correct permission
                $this->update(Table::USERPERMISSIONS, [
                    'name' => strtolower('sprout-module-sent-email:resendSentEmail'),
                ], [
                    'id' => $oldResendSentEmailPermissionId,
                ]);
            } else {
                // if the correct permission already exists,
                // migrate the old permission stuff to the correct ID
                $oldEditEmailPermissionIds = (new Query())
                    ->select(['id'])
                    ->from([Table::USERPERMISSIONS])
                    ->where([
                        'in', 'name', [
                            strtolower('sprout-module-sent-email:resendEmails'),
                        ],
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

                $this->delete(Table::USERPERMISSIONS, [
                    'in', 'id', $oldEditEmailPermissionIds,
                ]);

                $this->delete(Table::USERPERMISSIONS_USERS, [
                    'in', 'permissionId', $oldEditEmailPermissionIds,
                ]);

                $this->delete(Table::USERPERMISSIONS_USERGROUPS, [
                    'in', 'permissionId', $oldEditEmailPermissionIds,
                ]);

                $userIdsWithEditEmailPermission = array_unique($userPermissionWithEditEmailPermissionIds);

                // Create User and User Group permission for new Permission ID
                foreach ($userIdsWithEditEmailPermission as $userId) {
                    $this->insert(Table::USERPERMISSIONS_USERS, [
                        'userId' => $userId,
                        'permissionId' => $resendSentEmailPermissionId,
                    ]);
                }

                $groupIdsWithEditEmailPermission = array_unique($userGroupPermissionsWithEditEmailPermissionIds);

                foreach ($groupIdsWithEditEmailPermission as $groupId) {
                    $this->insert(Table::USERPERMISSIONS_USERGROUPS, [
                        'groupId' => $groupId,
                        'permissionId' => $resendSentEmailPermissionId,
                    ]);
                }
            }
        }
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
