<?php

namespace BarrelStrength\Sprout\meta\migrations;

use craft\db\Migration;
use craft\db\Table;

class m211101_000003_update_field_types extends Migration
{
    public function safeUp(): void
    {
        $types = [
            [
                'oldType' => 'barrelstrength\sproutseo\fields\ElementMetadata',
                'newType' => 'BarrelStrength\Sprout\meta\components\fields\ElementMetadataField',
            ],
        ];

        foreach ($types as $type) {
            $this->update(Table::FIELDS, [
                'type' => $type['newType'],
            ], ['type' => $type['oldType']], [], false);
        }
    }

    public function safeDown(): bool
    {
        echo "m211101_000003_update_field_types cannot be reverted.\n";

        return false;
    }
}
