<?php

namespace BarrelStrength\Sprout\meta\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Json;

class m211101_000002_update_field_settings extends Migration
{
    public function safeUp(): void
    {
        $fields = (new Query())
            ->select(['id', 'settings'])
            ->from('{{%fields}}')
            ->where(['type' => 'barrelstrength\sproutseo\fields\ElementMetadata'])
            ->all();

        // Remove deprecated attributes and resave settings
        foreach ($fields as $field) {
            $id = $field['id'];
            $settings = Json::decode($field['settings']);

            unset(
                // Changed
                $settings['enableMetaDetailsSearch'],
                $settings['enableMetaDetailsOpenGraph'],
                $settings['enableMetaDetailsTwitterCard'],
                $settings['enableMetaDetailsGeo'],
                $settings['enableMetaDetailsRobots'],
                $settings['dateCreated'],
                $settings['dateUpdated'],
                $settings['uid'],
                $settings['elementId'],

                // Legacy attributes that might exist in an outlier
                $settings['metadata'],
                $settings['values'],
                $settings['showMainEntity'],

                // Migration m190913_152146_update_preview_targets triggered error looking for this
                $settings['meta'],
            );

            $this->update(Table::FIELDS, [
                'settings' => Json::encode($settings),
            ], ['id' => $id], [], false);
        }
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
