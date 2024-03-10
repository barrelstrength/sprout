<?php

namespace BarrelStrength\Sprout\meta\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

class m211101_000005_migrate_metadata_tables extends Migration
{
    public const GLOBAL_METADATA_TABLE = '{{%sprout_global_metadata}}';
    public const OLD_GLOBALS_TABLE = '{{%sproutseo_globals}}';

    public function safeUp(): void
    {
        $cols = [
            'id',
            'siteId',
            'identity',
            'ownership',
            'contacts',
            'social',
            'robots',
            'settings',
            'dateCreated',
            'dateUpdated',
            'uid',
        ];

        if ($this->getDb()->tableExists(self::OLD_GLOBALS_TABLE)) {

            $rows = (new Query())
                ->select($cols)
                ->from([self::OLD_GLOBALS_TABLE])
                ->all();

            $defaultImageMapping = [
                'sproutSeo-socialSquare' => 'sprout-socialSquare',
                'sproutSeo-ogRectangle' => 'sprout-ogRectangle',
                'sproutSeo-twitterRectangle' => 'sprout-twitterRectangle',
            ];

            foreach ($rows as &$row) {
                $settings = Json::decode($row['settings']);

                if (isset($settings['ogTransform'])) {
                    $settings['ogTransform'] = $defaultImageMapping[$settings['ogTransform']] ?? $settings['ogTransform'];
                }

                if (isset($settings['twitterTransform'])) {
                    $settings['twitterTransform'] = $defaultImageMapping[$settings['twitterTransform']] ?? $settings['twitterTransform'];
                }

                $row['settings'] = Json::encode($settings);
            }

            unset($row);

            Craft::$app->getDb()->createCommand()
                ->batchInsert(self::GLOBAL_METADATA_TABLE, $cols, $rows)
                ->execute();
        }
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
