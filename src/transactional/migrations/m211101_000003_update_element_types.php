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
    }

    public function safeDown(): bool
    {
        echo "m211101_000003_update_element_types cannot be reverted.\n";

        return false;
    }
}
