<?php

namespace BarrelStrength\Sprout\mailer\components\audiences;

use BarrelStrength\Sprout\mailer\audience\AudienceType;
use BarrelStrength\Sprout\mailer\components\mailers\MailingListRecipient;
use BarrelStrength\Sprout\mailer\db\SproutTable;
use Craft;
use craft\elements\User;

class SubscriberListAudienceType extends AudienceType
{
    public ?int $subscriberListId = null;

    public static function displayName(): string
    {
        return Craft::t('sprout-module-mailer', 'Subscriber List');
    }

    public function getHandle(): string
    {
        return 'subscriber-list';
    }

    public function getSettingsHtml(): ?string
    {
        return '';
    }

    public function getRecipients(): array
    {
        $users = User::find()
            ->innerJoin([
                'subscriptions' => SproutTable::SUBSCRIPTIONS,
            ], '[[users.id]] = [[subscriptions.itemId]]')
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
