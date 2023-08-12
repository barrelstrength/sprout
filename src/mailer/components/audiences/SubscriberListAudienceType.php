<?php

namespace BarrelStrength\Sprout\mailer\components\audiences;

use BarrelStrength\Sprout\mailer\audience\AudienceType;
use BarrelStrength\Sprout\mailer\components\elements\subscriber\SproutSubscriberQueryBehavior;
use BarrelStrength\Sprout\mailer\components\mailers\MailingListRecipient;
use Craft;
use craft\elements\User;
use craft\helpers\UrlHelper;

class SubscriberListAudienceType extends AudienceType
{
    public ?array $userStatuses = null;

    public static function displayName(): string
    {
        return Craft::t('sprout-module-mailer', 'Subscriber List');
    }

    public function getColumnAttributeHtml(): string
    {
        // TODO - update how settings are stored so the Audience Type gets populated correctly
        // https://sprout-dev.ddev.site/admin/users?site=en_us&source=subscriber-lists%3A90
        // https://sprout-dev.ddev.site/admin/users/all?site=en_us&source=*
        $editUrl = UrlHelper::cpUrl('users', [
            'source' => 'subscriber-lists:' . $this->id,
        ]);

        return '<a href="' . $editUrl . '" class="go">' .
            Craft::t('sprout-module-mailer', 'Subscriber List') . '</a>';
    }

    public function getSettingsHtml(): ?string
    {
        $userStatusOptions = [
            [
                'label' => Craft::t('sprout-module-mailer', 'Active'),
                'value' => User::STATUS_ACTIVE,
            ],
            [
                'label' => Craft::t('sprout-module-mailer', 'Pending'),
                'value' => User::STATUS_PENDING,
            ],
            [
                'label' => Craft::t('sprout-module-mailer', 'Inactive'),
                'value' => User::STATUS_INACTIVE,
            ],
            [
                'label' => Craft::t('sprout-module-mailer', 'Suspended'),
                'value' => User::STATUS_SUSPENDED,
            ],
            [
                'label' => Craft::t('sprout-module-mailer', 'Locked'),
                'value' => User::STATUS_LOCKED,
            ],
        ];

        return Craft::$app->getView()->renderTemplate('sprout-module-mailer/_components/audiences/SubscriberList/settings.twig', [
            'audienceType' => $this,
            'userStatusOptions' => $userStatusOptions,
        ]);
    }

    public function getRecipients(): array
    {
        $query = User::find();

        /** @var SproutSubscriberQueryBehavior $query */
        $users = $query->sproutSubscriberListId($this->id)
            ->all();

        $userStatuses = $this->userStatuses;

        $usersWithSelectedStatuses = array_filter($users, static function($user) use ($userStatuses) {
            return in_array($user->getStatus(), $userStatuses, true);
        });

        $recipients = array_map(static function($user) {
            return new MailingListRecipient([
                'name' => $user->getFriendlyName(),
                'email' => $user->email,
            ]);
        }, $usersWithSelectedStatuses);

        return $recipients;
    }
}
