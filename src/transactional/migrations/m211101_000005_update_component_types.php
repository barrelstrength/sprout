<?php

namespace BarrelStrength\Sprout\transactional\migrations;

use craft\db\Migration;

class m211101_000005_update_component_types extends Migration
{
    public const OLD_NOTIFICATIONS_TABLE = '{{%sproutemail_notificationemails}}';

    public function safeUp(): void
    {
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
                'oldType' => 'barrelstrength\sproutemail\events\notificationevents\UserDeletedNotificationEvent',
                'newType' => 'BarrelStrength\Sprout\transactional\components\notificationevents\UsersDelete',
            ],
            [
                'oldType' => 'barrelstrength\sproutemail\events\notificationevents\UserLoggedInNotificationEvent',
                'newType' => 'BarrelStrength\Sprout\transactional\components\notificationevents\UsersLogin',
            ],
            [
                // Temporarily update all of these to UserUpdatedNotificationEvent
                // And later when checking the event settings we can update any with isNew:true
                // to UserCreatedNotificationEvent
                'oldType' => 'barrelstrength\sproutemail\events\notificationevents\UsersSave',
                'newType' => 'BarrelStrength\Sprout\transactional\components\notificationevents\UserUpdatedNotificationEvent',
            ],

            // Forms
            [
                'oldType' => 'barrelstrength\sproutforms\integrations\sproutemail\events\notificationevents\SaveEntryEvent',
                'newType' => 'BarrelStrength\Sprout\forms\components\notificationevents\SaveSubmissionNotificationEvent',
            ],
        ];

        foreach ($types as $type) {
            if (!$this->db->columnExists(self::OLD_NOTIFICATIONS_TABLE, 'eventId')) {
                continue;
            }

            $this->update(self::OLD_NOTIFICATIONS_TABLE, [
                'eventId' => $type['newType'],
            ], [
                'eventId' => $type['oldType'],
            ], [], false);
        }
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
