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
            'uniqueKey',
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
            'sitemapKey',
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

        $sourceKeyMapping = [
            'BarrelStrength\Sprout\sitemaps\components\sitemapmetadata\EntrySitemapMetadata' => 'entries',
            'BarrelStrength\Sprout\sitemaps\components\sitemapmetadata\CategorySitemapMetadata' => 'categories',
            'BarrelStrength\Sprout\sitemaps\components\sitemapmetadata\ProductSitemapMetadata' => 'products',
        ];

        if ($this->getDb()->tableExists(self::OLD_SITEMAPS_TABLE)) {
            $rows = (new Query())
                ->select($oldCols)
                ->from([self::OLD_SITEMAPS_TABLE])
                ->all();

            // update urlEnabledSectionId to sourceKey
            foreach ($rows as &$row) {
                // Only modify Element Sitemap Metadata
                if (!empty($row['type'])) {
                    $sourceKey = $sourceKeyMapping[$row['type']] . '-' . $row['urlEnabledSectionId'];

                    $row['sourceKey'] = $sourceKey;
                } else {
                    $row['sourceKey'] = 'custom-pages';
                }

                $row['priority'] = (float)$row['priority'];

                // Unset old column on all rows
                unset($row['urlEnabledSectionId']);
            }

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
