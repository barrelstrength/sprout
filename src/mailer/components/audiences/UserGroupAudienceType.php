<?php

namespace BarrelStrength\Sprout\mailer\components\audiences;

use BarrelStrength\Sprout\mailer\audience\AudienceType;
use BarrelStrength\Sprout\mailer\components\mailers\MailingListRecipient;
use Craft;
use craft\elements\User;
use craft\helpers\UrlHelper;
use craft\models\UserGroup;
use craft\records\UserGroup as UserGroupRecord;
use Illuminate\Support\Collection;

class UserGroupAudienceType extends AudienceType
{
    public ?string $userGroupUid = null;

    public static function displayName(): string
    {
        return Craft::t('sprout-module-mailer', 'User Group');
    }

    public function getHandle(): string
    {
        return 'user-group';
    }

    public function getColumnAttributeHtml(): string
    {
        // TODO - update how settings are stored so the Audience Type gets populated correctly
        // https://sprout-dev.ddev.site/admin/users?site=en_us&source=subscriber-lists%3A90
        // https://sprout-dev.ddev.site/admin/users/all?site=en_us&source=*
        $editUrl = UrlHelper::cpUrl('users', [
            'source' => 'group:' . $this->userGroupUid,
        ]);

        return '<a href="' . $editUrl . '" class="go">' .
            Craft::t('sprout-module-mailer', 'User Group') . '</a>';
    }

    public function getSettingsHtml(): ?string
    {
        $groupOptions = Collection::make(Craft::$app->getUserGroups()->getAllGroups())
            ->map(fn(UserGroup $group) => [
                'label' => Craft::t('site', $group->name),
                'value' => $group->uid,
            ])
            ->all();

        return Craft::$app->getView()->renderTemplate('sprout-module-mailer/_components/audiences/userGroupSettings', [
            'audienceType' => $this,
            'groupOptions' => $groupOptions,
        ]);
    }

    public function getRecipients(): array
    {
        $userGroupId = UserGroupRecord::find()
            ->select('id')
            ->where(['uid' => $this->userGroupUid])
            ->scalar();

        $users = User::find()
            ->groupId($userGroupId)
            ->all();

        $recipients = array_map(static function($user) {
            return new MailingListRecipient([
                'name' => $user->getFriendlyName(),
                'email' => $user->email,
            ]);
        }, $users);

        return $recipients;
    }
}
