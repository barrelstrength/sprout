<?php

namespace BarrelStrength\Sprout\redirects\migrations;

use craft\db\Migration;
use craft\db\Table;

class m211101_000003_update_element_types extends Migration
{
    public function safeUp(): void
    {
        $types = [
            [
                'oldType' => 'barrelstrength\sproutbaseredirects\elements\Redirect',
                'newType' => 'BarrelStrength\Sprout\redirects\components\elements\RedirectElement',
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
