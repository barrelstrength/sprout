<?php

namespace BarrelStrength\Sprout\transactional\migrations;

use craft\db\Migration;

class m211101_000005_update_component_types extends Migration
{
    public function safeUp(): void
    {
        $components = [
            'sproutemail_notificationemails' => [
                'emailTemplateId' => [
                    // Email
                    [
                        'oldType' => 'barrelstrength\sproutbaseemail\emailtemplates\BasicTemplates',
                        'newType' => 'BarrelStrength\Sprout\mailer\components\emailthemes\DefaultEmailTemplates',
                    ],

                    // Forms
                    [
                        'oldType' => 'barrelstrength\sproutforms\integrations\sproutemail\emailtemplates\basic\BasicSproutFormsNotification',
                        'newType' => 'BarrelStrength\Sprout\transactional\components\emailthemes\BasicSproutFormsNotification',
                    ],
                ],
                'eventId' => [
                    // Email
                    [
                        'oldType' => 'barrelstrength\sproutemail\events\notificationevents\EntriesDelete',
                        'newType' => 'BarrelStrength\Sprout\transactional\components\notificationevents\EntriesDelete',
                    ],
                    [
                        'oldType' => 'barrelstrength\sproutemail\events\notificationevents\EntriesSave',
                        'newType' => 'BarrelStrength\Sprout\transactional\components\notificationevents\EntriesSave',
                    ],
                    [
                        'oldType' => 'barrelstrength\sproutemail\events\notificationevents\Manual',
                        'newType' => 'BarrelStrength\Sprout\transactional\components\notificationevents\Manual',
                    ],
                    [
                        'oldType' => 'barrelstrength\sproutemail\events\notificationevents\UsersActivate',
                        'newType' => 'BarrelStrength\Sprout\transactional\components\notificationevents\UsersActivate',
                    ],
                    [
                        'oldType' => 'barrelstrength\sproutemail\events\notificationevents\UsersDelete',
                        'newType' => 'BarrelStrength\Sprout\transactional\components\notificationevents\UsersDelete',
                    ],
                    [
                        'oldType' => 'barrelstrength\sproutemail\events\notificationevents\UsersLogin',
                        'newType' => 'BarrelStrength\Sprout\transactional\components\notificationevents\UsersLogin',
                    ],
                    [
                        'oldType' => 'barrelstrength\sproutemail\events\notificationevents\UsersSave',
                        'newType' => 'BarrelStrength\Sprout\transactional\components\notificationevents\UsersSave',
                    ],

                    // Forms
                    [
                        'oldType' => 'barrelstrength\sproutforms\integrations\sproutemail\events\notificationevents\SaveEntryEvent',
                        'newType' => 'BarrelStrength\Sprout\transactional\components\notificationevents\SaveEntryEvent',
                    ],
                ],
            ],
        ];

        foreach ($components as $dbTableName => $columns) {
            foreach ($columns as $column => $types) {
                foreach ($types as $type) {

                    $dbTable = '{{%' . $dbTableName . '}}';

                    if (!$this->db->columnExists($dbTable, $column)) {
                        continue;
                    }

                    $this->update($dbTable, [
                        $column => $type['newType'],
                    ], [
                        $column => $type['oldType'],
                    ], [], false);
                }
            }
        }
    }

    public function safeDown(): bool
    {
        echo "m211101_000005_update_component_types cannot be reverted.\n";

        return false;
    }
}
