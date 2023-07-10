<?php

namespace BarrelStrength\Sprout\transactional\components\conditions;

use Craft;
use craft\base\conditions\BaseMultiSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\ArrayHelper;

class UserGroupForNewUserConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    public function getLabel(): string
    {
        return Craft::t('sprout-module-transactional', 'User Group (New User Event)');
    }

    public function getExclusiveQueryParams(): array
    {
        return [];
    }

    protected function options(): array
    {
        $groups = Craft::$app->getUserGroups()->getAllGroups();

        return array_map(static function($group) {
            return [
                'label' => $group->name,
                'value' => $group->handle,
            ];
        }, $groups);
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        // No changes
    }

    public function matchElement(ElementInterface $element): bool
    {
        $usersGroupIds = $element->getGroups();

        // Get an array of the users User Groups and make sure the ID's are strings to match
        // the IDs in our other arrays which have the IDs as strings
        $currentUserGroupIds = ArrayHelper::getColumn($usersGroupIds, 'id');
        $currentUserGroupIds = array_map(static fn($id) => (string)$id, $currentUserGroupIds);

        // When saving a new user, we get the user groups from the post request
        // because the groups are saved in a separate event after the initial save:
        // UsersController::EVENT_AFTER_ASSIGN_GROUPS_AND_PERMISSIONS
        $postedUserGroupIds = Craft::$app->request->getBodyParam('groups');

        if ($postedUserGroupIds === null) {
            return false;
        }

        if ($postedUserGroupIds === '') {
            $postedUserGroupIds = [];
        }

        $savedAndDynamicUserGroupIds = array_unique([...$currentUserGroupIds, ...$postedUserGroupIds]);

        // If the user being saved is not in one of the groups that triggers this event
        if (empty(array_intersect($this->getValues(), $savedAndDynamicUserGroupIds))) {
            return false;
        }

        return true;
    }
}
