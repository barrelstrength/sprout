<?php

namespace BarrelStrength\Sprout\mailer\components\datasources;

use BarrelStrength\Sprout\datastudio\components\elements\DataSetElement;
use BarrelStrength\Sprout\datastudio\datasources\DataSource;
use BarrelStrength\Sprout\mailer\components\audiences\SubscriberListAudienceType;
use BarrelStrength\Sprout\mailer\components\elements\audience\AudienceElement;
use BarrelStrength\Sprout\mailer\components\elements\subscriber\SproutSubscriberQueryBehavior;
use Craft;
use craft\elements\User;

/**
 * @property string $defaultEmailColumn
 */
class SubscriberListDataSource extends DataSource
{
    public ?int $subscriberListId = null;

    public static function displayName(): string
    {
        return Craft::t('sprout-module-mailer', 'Subscriber List');
    }

    public function getDescription(): string
    {
        return Craft::t('sprout-module-mailer', 'Create a Subscriber List with your Subscribers');
    }

    public function isEmailColumnEditable(): bool
    {
        return false;
    }

    public function getDefaultEmailColumn(): string
    {
        return 'email';
    }

    public function getResults(DataSetElement $dataSet): array
    {
        $query = User::find();

        /** @var SproutSubscriberQueryBehavior $query */
        $users = $query->sproutSubscriberListId($this->subscriberListId)
            ->all();

        $subscribers = [];

        foreach ($users as $user) {
            $subscribers[] = [
                'id' => $user->id,
                'email' => $user->email,
                'firstName' => $user->firstName,
                'lastName' => $user->lastName,
                'username' => $user->username,
                'admin' => $user->admin,
                'enabled' => $user->enabled,
            ];
        }

        return $subscribers;
    }

    public function getSettingsHtml(array $settings = []): ?string
    {
        /** @var AudienceElement[] $audiences */
        $audiences = AudienceElement::find()
            ->type(SubscriberListAudienceType::class)
            ->all();

        $subscriberListOptions = array_map(static function($audience) {
            return [
                'label' => $audience->name,
                'value' => $audience->id,
            ];
        }, $audiences);

        return Craft::$app->getView()->renderTemplate('sprout-module-mailer/_components/datasources/SubscriberList/settings.twig', [
            'subscriberListOptions' => $subscriberListOptions,
        ]);
    }
}
