<?php

namespace BarrelStrength\Sprout\datastudio\components\datasources;

use BarrelStrength\Sprout\datastudio\components\elements\DataSetElement;
use BarrelStrength\Sprout\datastudio\datasources\DataSource;
use Craft;
use craft\db\Query;
use craft\db\Table;

class UsersDataSource extends DataSource
{
    public array $userGroupIds = [];

    public bool $displayUserGroupColumns = false;

    public static function getHandle(): string
    {
        return 'users';
    }

    public static function displayName(): string
    {
        return Craft::t('sprout-module-data-studio', 'Users & User Groups');
    }

    public function getDescription(): string
    {
        return Craft::t('sprout-module-data-studio', 'Create data sets about your users and user groups.');
    }

    public function getResults(DataSetElement $dataSet): array
    {
        $includeAdmins = false;

        if (in_array('admin', $this->userGroupIds, false)) {
            $includeAdmins = true;

            // Admin is always the first in our array if it exists
            unset($this->userGroupIds[0]);
        }

        $userGroups = Craft::$app->getUserGroups()->getAllGroups();

        $selectQueryString = 'users.id,
            users.username AS Username,
            users.email AS Email,
            (users.firstName) AS [[First Name]],
            (users.lastName) AS [[Last Name]]';

        if ($this->displayUserGroupColumns) {
            $selectQueryString .= ',users.admin AS Admin';
        }

        $query = new Query();
        $userQuery = $query
            ->select($selectQueryString)
            ->from(['users' => Table::USERS])
            ->leftJoin(['usergroups_users' => Table::USERGROUPS_USERS], 'users.id = [[usergroups_users.userId]]');

        if (count($this->userGroupIds)) {
            $userQuery->where(['in', '[[usergroups_users.groupId]]', $this->userGroupIds]);
        }

        if ($includeAdmins) {
            $userQuery->orWhere([
                'users.admin' => true,
            ]);
        }

        $userQuery->groupBy('users.id');

        $users = $userQuery->all();

        // Update users to be indexed by their ids
        $usersById = [];
        foreach ($users as $user) {
            $usersById[$user['id']] = $user;
            unset ($usersById[$user['id']]['id']);
        }

        $query = new Query();
        $userGroupsMapQuery = $query
            ->select('*')
            ->from(['usergroups_users' => Table::USERGROUPS_USERS])
            ->leftJoin(['usergroups' => Table::USERGROUPS], 'usergroups.id = [[usergroups_users.groupId]]')
            ->all();

        // Create a map of all users and which user groups they are in
        $userGroupsMap = [];
        foreach ($userGroupsMapQuery as $userGroupsUser) {
            $userGroupsMap[$userGroupsUser['userId']][$userGroupsUser['name']] = true;
        }

        // Add and identify User Groups as columns
        foreach ($usersById as $userId => $user) {
            if ($this->displayUserGroupColumns) {
                // Add User Groups as columns to user array
                foreach ($userGroups as $userGroup) {
                    $inGroup = $userGroupsMap[$userId][$userGroup->name] ?? false;
                    $user[$userGroup->name] = (int)$inGroup;
                }
            }

            $usersById[$userId] = $user;
        }

        return $usersById;
    }

    public function getSettingsHtml(): ?string
    {
        $userGroupSettings = [];
        $userGroups = Craft::$app->getUserGroups()->getAllGroups();

        $userGroupSettings[] = [
            'label' => 'Admin',
            'value' => 'admin',
        ];

        foreach ($userGroups as $userGroup) {
            $userGroupSettings[] = [
                'label' => $userGroup->name,
                'value' => $userGroup->id,
            ];
        }

        $settingsErrors = $this->dataSet->getErrors('settings');
        $settingsErrors = array_shift($settingsErrors);

        return Craft::$app->getView()->renderTemplate('sprout-module-data-studio/_components/datasources/Users/settings.twig', [
            'userGroupSettings' => $userGroupSettings,
            'settings' => $this->dataSet->getDataSource()->getSettings(),
            'errors' => $settingsErrors,
        ]);
    }
}
