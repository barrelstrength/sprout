<?php

namespace BarrelStrength\Sprout\transactional\migrations;

use craft\db\Migration;
use craft\db\Table;

class m211101_000003_update_element_types extends Migration
{
    public function safeUp(): void
    {
        $types = [
            [
                'oldType' => 'barrelstrength\sproutbaseemail\elements\NotificationEmail',
                'newType' => 'BarrelStrength\Sprout\transactional\components\elements\TransactionalEmailElement',
            ],
        ];

        foreach ($types as $type) {
            $this->update(Table::ELEMENTS, [
                'type' => $type['newType'],
            ], ['type' => $type['oldType']], [], false);
        }

        // Update Field Layout Classes from Craft 2
        $this->update(Table::FIELDLAYOUTS, [
            'type' => 'BarrelStrength\Sprout\transactional\components\elements\TransactionalEmailElement',
        ], ['type' => 'SproutEmail_NotificationEmail'], [], false);

        // Update Field Layout Classes from Craft 3
        $this->update(Table::FIELDLAYOUTS, [
            'type' => 'BarrelStrength\Sprout\transactional\components\elements\TransactionalEmailElement',
        ], ['type' => 'barrelstrength\sproutbaseemail\elements\NotificationEmail'], [], false);
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
