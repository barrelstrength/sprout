<?php

namespace BarrelStrength\Sprout\datastudio\migrations;

use craft\db\Migration;
use craft\db\Table;

class m211101_000006_update_widget_types extends Migration
{
    public function safeUp(): void
    {
        $types = [
            [
                'oldType' => 'barrelstrength\sproutbasereports\widgets\Number',
                'newType' => 'BarrelStrength\Sprout\datastudio\components\widgets\Number',
            ],
            [
                'oldType' => 'barrelstrength\sproutbasereports\widgets\Visualization',
                'newType' => 'BarrelStrength\Sprout\datastudio\components\widgets\Visualization',
            ],
        ];

        foreach ($types as $type) {
            $this->update(Table::WIDGETS, [
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
