<?php

namespace BarrelStrength\Sprout\datastudio\migrations;

use craft\db\Migration;
use craft\db\Table;

class m211101_000004_update_element_types extends Migration
{
    public function safeUp(): void
    {
        $types = [
            [
                'oldType' => 'barrelstrength\sproutbasereports\elements\Report',
                'newType' => 'BarrelStrength\Sprout\datastudio\components\elements\DataSetElement',
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
        echo "m211101_000004_update_element_types cannot be reverted.\n";

        return false;
    }
}
