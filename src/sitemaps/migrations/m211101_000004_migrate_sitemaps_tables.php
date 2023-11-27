<?php

namespace BarrelStrength\Sprout\sitemaps\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;

class m211101_000004_migrate_sitemaps_tables extends Migration
{
    public const SITEMAPS_TABLE = '{{%sprout_sitemaps_metadata}}';
    public const OLD_SITEMAPS_TABLE = '{{%sproutseo_sitemaps}}';

    public function safeUp(): void
    {
        $oldCols = [
            'id',
            'siteId',
            'urlEnabledSectionId',
            'enabled',
            'type',
            'uri',
            'priority',
            'changeFrequency',
            'dateCreated',
            'dateUpdated',
            'uid',
        ];

        $newCols = [
            'id',
            'siteId',
            'enabled',
            'type',
            'uri',
            'priority',
            'changeFrequency',
            'dateCreated',
            'dateUpdated',
            'uid',

            'sourceKey',
        ];

        if ($this->getDb()->tableExists(self::OLD_SITEMAPS_TABLE)) {
            $rows = (new Query())
                ->select($oldCols)
                ->from([self::OLD_SITEMAPS_TABLE])
                ->all();

            // update urlEnabledSectionId to sourceKey
            foreach ($rows as &$row) {
                $sourceKey = null;

                if ($row['type'] === 'craft\elements\Entry') {
                    $row['uri'] = null;
                    $sourceKey = (new Query())
                        ->select(['uid'])
                        ->from(['{{%sections}}'])
                        ->where(['id' => $row['urlEnabledSectionId']])
                        ->scalar();
                }

                if ($row['type'] === 'craft\elements\Category') {
                    $row['uri'] = null;
                    $sourceKey = (new Query())
                        ->select(['uid'])
                        ->from(['{{%categorygroups}}'])
                        ->where(['id' => $row['urlEnabledSectionId']])
                        ->scalar();
                }

                if ($row['type'] === 'craft\commerce\elements\Product') {
                    $row['uri'] = null;
                    $sourceKey = (new Query())
                        ->select(['uid'])
                        ->from(['{{%commerce_producttypes}}'])
                        ->where(['id' => $row['urlEnabledSectionId']])
                        ->scalar();
                }

                // this might be null or an empty string
                if (empty($row['type'])) {
                    $row['type'] = null;
                    $sourceKey = 'custom-pages';
                }

                $row['sourceKey'] = $sourceKey;
                $row['priority'] = (float)$row['priority'];

                // Unset old column on all rows
                unset($row['urlEnabledSectionId']);
            }

            unset($row);

            Craft::$app->getDb()->createCommand()
                ->batchInsert(self::SITEMAPS_TABLE, $newCols, $rows)
                ->execute();
        }
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
