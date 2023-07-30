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
    public static function displayName(): string
    {
        return Craft::t('sprout-module-mailer', 'Subscriber List');
    }

    public function getHandle(): string
    {
        return 'subscriber-list';
    }

    public function getColumnAttributeHtml(): string
    {
        // TODO - update how settings are stored so the Audience Type gets populated correctly
        // https://sprout-dev.ddev.site/admin/users?site=en_us&source=subscriber-lists%3A90
        // https://sprout-dev.ddev.site/admin/users/all?site=en_us&source=*
        $editUrl = UrlHelper::cpUrl('users', [
            'source' => 'subscriber-lists:' . $this->elementId,
        ]);

        return '<a href="' . $editUrl . '" class="go">' .
            Craft::t('sprout-module-mailer', 'Subscriber List') . '</a>';
    }

    public function getSettingsHtml(): ?string
    {
        return '';
    }

    public function getRecipients(): array
    {
        $query = User::find();

        /** @var SproutSubscriberQueryBehavior $query */
        $users = $query->sproutSubscriberListId($this->elementId)
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
