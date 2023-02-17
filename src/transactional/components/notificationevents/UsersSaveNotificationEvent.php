<?php

namespace BarrelStrength\Sprout\transactional\components\notificationevents;

use BarrelStrength\Sprout\transactional\notificationevents\NotificationEvent;
use Craft;
use craft\elements\User;
use craft\events\ModelEvent;
use craft\helpers\ArrayHelper;
use Exception;
use yii\base\Event;

class UsersSaveNotificationEvent extends NotificationEvent
{
    public bool $whenNew = false;

    public bool $whenUpdated = false;

    public array $groups = [];

    public array|string $userGroupIds = [];

    public bool $sendToAdmins = false;

    public static function displayName(): string
    {
        return Craft::t('sprout-module-transactional', 'When a user is saved');
    }

    public static function getEventClassName(): ?string
    {
        return User::class;
    }

    public static function getEventName(): ?string
    {
        return User::EVENT_AFTER_SAVE;
    }

    public function getDescription(): string
    {
        return Craft::t('sprout-module-transactional', 'Triggered when a user is saved.');
    }

    public function getSettingsHtml(): ?string
    {
        if (!$this->groups) {
            $this->groups = $this->getAllGroups();
        }

        return Craft::$app->getView()->renderTemplate('sprout-module-transactional/_components/events/saveUser', [
            'event' => $this,
            '',
        ]);
    }

    public function getEventObject(): ?object
    {
        $event = $this->event ?? null;

        return $event->sender ?? null;
    }

    public function getMockEventObject()
    {
        $criteria = User::find();

        $ids = $this->userGroupIds;

        if (is_array($ids) && count($ids)) {
            $id = array_shift($ids);

            $criteria->groupId = $id;
        }

        return $criteria->one();
    }

    public function isSendable(Event $event): bool
    {
        if (!$event instanceof ModelEvent) {
            return false;
        }

        /** @var User $user */
        $user = $event->sender;

        if (!$user instanceof User) {
            return false;
        }

        if (!$this->sendToAdmins && $user->admin) {
            return false;
        }

        if (!$this->matchesWhenCondition($event)) {
            return false;
        }

        if (!$this->matchesSelectedUserGroupIds($user)) {
            return false;
        }

        return false;
    }

    public function matchesWhenCondition(Event $event): bool
    {
        $isNewEntry = $event->isNew ?? false;

        $matchesWhenNew = $this->whenNew && $isNewEntry ?? false;
        $matchesWhenUpdated = $this->whenUpdated && !$isNewEntry ?? false;

        if (!$matchesWhenNew && !$matchesWhenUpdated) {
            return false;
        }

        // Make sure new entries are new.
        if (($this->whenNew && !$isNewEntry) && !$this->whenUpdated) {
            return false;
        }

        // Make sure updated entries are not new
        if (($this->whenUpdated && $isNewEntry) && !$this->whenNew) {
            return false;
        }

        return true;
    }

    public function matchesSelectedUserGroupIds(User $user): bool
    {
        // Trigger when check all is ticked
        if ($this->userGroupIds === '*') {
            return true;
        }

        if (empty($this->userGroupIds)) {
            return false;
        }

        // Get an array of the users User Groups and make sure the ID's are strings to match
        // the IDs in our other arrays which have the IDs as strings
        $currentUserGroupIds = ArrayHelper::getColumn($user->getGroups(), 'id');
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
        if (empty(array_intersect($this->userGroupIds, $savedAndDynamicUserGroupIds))) {
            return false;
        }

        return true;
    }

    /**
     * Returns an array of groups suitable for use in checkbox field
     */
    public function getAllGroups(): array
    {
        try {
            $groups = Craft::$app->userGroups->getAllGroups();
        } catch (Exception) {
            $groups = [];
        }

        $options = [];

        if (is_countable($groups) ? count($groups) : 0) {
            foreach ($groups as $group) {
                $options[] = [
                    'label' => $group->name,
                    'value' => $group->id,
                ];
            }
        }

        return $options;
    }
}
