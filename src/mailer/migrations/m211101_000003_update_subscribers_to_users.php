<?php

namespace BarrelStrength\Sprout\mailer\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;

class m211101_000003_update_subscribers_to_users extends Migration
{
    public const AUDIENCES_TABLE = '{{%sprout_audiences}}';

    public const SUBSCRIPTIONS_TABLE = '{{%sprout_subscriptions}}';

    public const OLD_LISTS_TABLE = '{{%sproutlists_lists}}';

    public const OLD_SUBSCRIPTIONS_TABLE = '{{%sproutlists_subscriptions}}';

    public const OLD_SUBSCRIBERS_TABLE = '{{%sproutlists_subscribers}}';

    public const OLD_SUBSCRIBER_TYPE = 'barrelstrength\sproutlists\elements\Subscriber';

    public const SUBSCRIBER_LIST_AUDIENCE_TYPE = 'BarrelStrength\Sprout\mailer\components\audiences\SubscriberListAudienceType';

    public function safeUp(): void
    {
        $this->migrateListsToAudiencesTable();

        // Get all subscribers
        $subscribers = (new Query())
            ->select([
                'id',
                'userId',
                'email',
                'firstName',
                'lastName',
            ])
            ->from(self::OLD_SUBSCRIBERS_TABLE)
            ->all();

        if (!$subscribers) {
            return;
        }

        $subscriptions = (new Query())
            ->select([
                'listId as audienceId',
                'itemId as userId',
            ])
            ->from(self::OLD_SUBSCRIPTIONS_TABLE)
            ->all();

        foreach ($subscribers as $subscriber) {
            $subscriberId = !empty($subscriber['id']) ? $subscriber['id'] : null;
            $userId = !empty($subscriber['userId']) ? $subscriber['userId'] : null;
            $email = !empty($subscriber['email']) ? $subscriber['email'] : null;

            if (!$userId && !$email) {
                continue;
            }

            $user = null;

            // Try to get the User by ID
            if ($userId) {
                $user = Craft::$app->getUsers()->getUserById($userId);
            }

            // Make sure we have a User
            if (!$user) {
                $user = Craft::$app->getUsers()->ensureUserByEmail($email);

                if (!$user->firstName) {
                    $user->firstName = $subscribers['firstName'];
                }

                if (!$user->lastName) {
                    $user->lastName = $subscribers['lastName'];
                }
            }

            // Mapping: List => Audience
            // This should be 1:1 since we migrate Lists to a new table, we can keep all the primary key IDs the same.
            // List ID => Subscriber List ID => Audience ID

            // Find all the subscriptions for this user and insert them into
            // the new subscriptions table with the new user ID
            foreach ($subscriptions as $subscription) {
                if ($subscription['userId'] === $subscriberId) {
                    $this->insert(self::SUBSCRIPTIONS_TABLE, [
                        'subscriberListId' => $subscription['audienceId'],
                        'userId' => $user->id,
                    ]);
                }
            }
        }

        // Get all IDS from $subscribers
        $elementIds = array_map(static function($subscriber) {
            return $subscriber['id'];
        }, $subscribers);

        // Delete Subscriber Elements
        $this->delete(Table::ELEMENTS, [
            'in', 'id', $elementIds,
        ]);

        // Make sure we got them all
        $this->delete(Table::ELEMENTS, [
            'type' => self::OLD_SUBSCRIBER_TYPE,
        ]);
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }

    public function migrateListsToAudiencesTable(): void
    {
        $oldCols = [
            'id',
            'elementId',
            'type',
            'name',
            'handle',
            'dateCreated',
            'dateUpdated',
            'uid',
        ];

        $newCols = [
            'id',
            'type',
            'name',
            'handle',
            'dateCreated',
            'dateUpdated',
            'uid',

            'settings',
        ];

        if ($this->getDb()->tableExists(self::OLD_LISTS_TABLE)) {
            $rows = (new Query())
                ->select($oldCols)
                ->from([self::OLD_LISTS_TABLE])
                ->all();

            // Set all types to SubscriberListAudienceType
            foreach ($rows as $key => $row) {
                $rows[$key]['type'] = self::SUBSCRIBER_LIST_AUDIENCE_TYPE;

                // Only Migrate ListElement lists.
                // All other types must be migrated manually.
                if ($rows[$key]['id'] != $rows[$key]['elementId']) {
                    continue;
                }

                unset($rows[$key]['elementId']);

                // {"userStatuses":["active"]}
                $rows[$key]['settings'] = [
                    'userStatuses' => ['active'],
                ];
            }

            Craft::$app->getDb()->createCommand()
                ->batchInsert(
                    self::AUDIENCES_TABLE, $newCols, $rows)
                ->execute();
        }
    }
}
