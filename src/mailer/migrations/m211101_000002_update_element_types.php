<?php

namespace BarrelStrength\Sprout\mailer\migrations;

use craft\db\Migration;
use craft\db\Table;

class m211101_000002_update_element_types extends Migration
{
    public function safeUp(): void
    {
        $types = [
            [
                'oldType' => 'barrelstrength\sproutlists\elements\ListElement',
                'newType' => 'BarrelStrength\Sprout\mailer\components\elements\audience\AudienceElement',
            ],
            [
                'oldType' => 'barrelstrength\sproutlists\elements\Subscriber',
                'newType' => 'BarrelStrength\Sprout\mailer\components\elements\subscriber\SubscriberElement',
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
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
