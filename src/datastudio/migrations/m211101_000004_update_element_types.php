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

        // Update Field Layout Classes from Craft 2
        $this->update(Table::FIELDLAYOUTS, [
            'type' => 'BarrelStrength\Sprout\forms\components\elements\SubmissionElement',
        ], ['type' => 'SproutForms_Form'], [], false);

        // Update Field Layout Classes from Craft 3
        $this->update(Table::FIELDLAYOUTS, [
            'type' => 'BarrelStrength\Sprout\forms\components\elements\SubmissionElement',
        ], ['type' => 'barrelstrength\sproutforms\elements\Form'], [], false);
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
