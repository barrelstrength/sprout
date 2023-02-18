<?php

namespace BarrelStrength\Sprout\forms\migrations;

use craft\db\Migration;
use craft\db\Table;

class m211101_000005_update_widget_types extends Migration
{
    public function safeUp(): void
    {
        $types = [
            [
                'oldType' => 'barrelstrength\sproutforms\widgets\RecentEntries',
                'newType' => 'BarrelStrength\Sprout\forms\components\widgets\RecentSubmissions',
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
