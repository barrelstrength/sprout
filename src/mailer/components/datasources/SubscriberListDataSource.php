<?php

namespace BarrelStrength\Sprout\mailer\components\datasources;

use BarrelStrength\Sprout\datastudio\components\elements\DataSetElement;
use BarrelStrength\Sprout\datastudio\datasources\DataSource;
use BarrelStrength\Sprout\mailer\db\SproutTable;
use Craft;
use craft\db\Query;
use craft\db\Table;
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

    public static function getHandle(): string
    {
        return 'subscriber-list-data-source';
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
        $users = User::find([
            'subscriberListId' => $this->subscriberListId,
        ])->all();

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
        $subscriberListOptions = (new Query())
            ->select([
                'label' => 'lists.name',
                'value' => 'lists.id',
            ])
            ->from(['lists' => SproutTable::AUDIENCES])
            ->leftJoin(['elements' => Table::ELEMENTS], '[[elements.id]] = [[lists.id]]')
            ->where([
                'elements.dateDeleted' => null,
            ])
            ->all();

        return Craft::$app->getView()->renderTemplate('sprout-module-mailer/_components/datasources/SubscriberList/settings.twig', [
            'subscriberListOptions' => $subscriberListOptions,
        ]);
    }
}
