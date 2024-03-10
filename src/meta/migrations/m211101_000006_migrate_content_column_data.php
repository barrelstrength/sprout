<?php

namespace BarrelStrength\Sprout\meta\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\ElementHelper;
use craft\helpers\Json;

class m211101_000006_migrate_content_column_data extends Migration
{
    public function safeUp(): void
    {
        $fields = (new Query())
            ->select(['id', 'handle', 'columnSuffix'])
            ->from('{{%fields}}')
            ->where(['type' => 'BarrelStrength\Sprout\meta\components\fields\ElementMetadataField'])
            ->all();

        foreach ($fields as $field) {
            $fieldColumn = ElementHelper::fieldColumn('field_', $field['handle'], $field['columnSuffix']);

            // if column exists
            if (!$this->db->columnExists('{{%content}}', $fieldColumn)) {
                Craft::warning("Metadata not found in $fieldColumn. Field column may have different naming convention.", __METHOD__);
                continue;
            }

            $rows = (new Query())
                ->select(['id', $fieldColumn])
                ->from('{{%content}}')
                ->where(['not', [$fieldColumn => null]])
                ->all();

            $defaultImageMapping = [
                'sproutSeo-socialSquare' => 'sprout-socialSquare',
                'sproutSeo-ogRectangle' => 'sprout-ogRectangle',
                'sproutSeo-twitterRectangle' => 'sprout-twitterRectangle',
            ];

            foreach ($rows as &$row) {
                $settings = Json::decode($row[$fieldColumn]);

                if (isset($settings['ogTransform'])) {
                    $settings['ogTransform'] = $defaultImageMapping[$settings['ogTransform']] ?? $settings['ogTransform'];
                }

                if (isset($settings['twitterTransform'])) {
                    $settings['twitterTransform'] = $defaultImageMapping[$settings['twitterTransform']] ?? $settings['twitterTransform'];
                }

                $this->update('{{%content}}', [
                    $fieldColumn => Json::encode($settings),
                ], [
                    'id' => $row['id'],
                ]);
            }
        }
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
