<?php

namespace BarrelStrength\Sprout\transactional\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

class m211101_000005_update_component_types extends Migration
{
    public const OLD_NOTIFICATIONS_TABLE = '{{%sproutemail_notificationemails}}';

    public function safeUp(): void
    {
        if (!$this->db->columnExists(self::OLD_NOTIFICATIONS_TABLE, 'eventId')) {
            return;
        }

        $types = [
            // Email
            [
                'oldType' => 'barrelstrength\sproutemail\events\notificationevents\EntriesDelete',
                'newType' => 'BarrelStrength\Sprout\transactional\components\notificationevents\EntryDeletedNotificationEvent',
            ],
            [
                'oldType' => 'barrelstrength\sproutemail\events\notificationevents\EntriesSave',
                'newType' => 'BarrelStrength\Sprout\transactional\components\notificationevents\EntrySavedNotificationEvent',
            ],
            [
                'oldType' => 'barrelstrength\sproutemail\events\notificationevents\Manual',
                'newType' => 'BarrelStrength\Sprout\transactional\components\notificationevents\ManualNotificationEvent',
            ],
            [
                'oldType' => 'barrelstrength\sproutemail\events\notificationevents\UsersActivate',
                'newType' => 'BarrelStrength\Sprout\transactional\components\notificationevents\UserActivatedNotificationEvent',
            ],
            [

                'oldType' => 'barrelstrength\sproutemail\events\notificationevents\UsersDelete',
                'newType' => 'BarrelStrength\Sprout\transactional\components\notificationevents\UserDeletedNotificationEvent',
            ],
            [
                'oldType' => 'barrelstrength\sproutemail\events\notificationevents\UsersLogin',
                'newType' => 'BarrelStrength\Sprout\transactional\components\notificationevents\UserLoggedInNotificationEvent',
            ],

            // Forms
            [
                'oldType' => 'barrelstrength\sproutforms\integrations\sproutemail\events\notificationevents\SaveEntryEvent',
                'newType' => 'BarrelStrength\Sprout\forms\components\notificationevents\SaveSubmissionNotificationEvent',
            ],
        ];

        foreach ($types as $type) {
            $this->update(self::OLD_NOTIFICATIONS_TABLE, [
                'eventId' => $type['newType'],
            ], [
                'eventId' => $type['oldType'],
            ], [], false);
        }

        $oldType = 'barrelstrength\sproutemail\events\notificationevents\UsersSave';

        $userNotificationEvents = (new Query())
            ->select(['id', 'eventId', 'settings'])
            ->from([self::OLD_NOTIFICATIONS_TABLE])
            ->where(['eventId' => $oldType])
            ->all();

        foreach ($userNotificationEvents as $userNotificationEvent) {
            $settings = Json::decode($userNotificationEvent['settings'] ?? []);

            $whenNew = !empty($settings['whenNew']) ? true : false;

            $newType = 'BarrelStrength\Sprout\transactional\components\notificationevents\UserUpdatedNotificationEvent';

            if ($whenNew) {
                $newType = 'BarrelStrength\Sprout\transactional\components\notificationevents\UserCreatedNotificationEvent';
            }

            $this->update(self::OLD_NOTIFICATIONS_TABLE, [
                'eventId' => $newType,
            ], [
                'id' => $userNotificationEvent['id'],
            ], [], false);
        }
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
