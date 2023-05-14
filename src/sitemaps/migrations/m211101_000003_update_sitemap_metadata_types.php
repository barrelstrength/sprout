<?php

namespace BarrelStrength\Sprout\sitemaps\migrations;

use craft\db\Migration;

class m211101_000003_update_sitemap_metadata_types extends Migration
{
    public const OLD_SITEMAPS_TABLE = '{{%sproutseo_sitemaps}}';

    public function safeUp(): void
    {
        $types = [
            [
                'oldType' => 'barrelstrength\sproutbaseuris\sectiontypes\Entry',
                'newType' => 'craft\elements\Entry',
                'newSourceKeyPrefix' => 'entries',
            ],
            [
                'oldType' => 'barrelstrength\sproutbaseuris\sectiontypes\Category',
                'newType' => 'craft\elements\Category',
                'newSourceKeyPrefix' => 'categories',
            ],
            [
                'oldType' => 'barrelstrength\sproutbaseuris\sectiontypes\Product',
                'newType' => 'craft\commerce\elements\Product',
                'newSourceKeyPrefix' => 'products',
            ],
        ];

        foreach ($types as $type) {
            if (!$this->db->columnExists(self::OLD_SITEMAPS_TABLE, 'type')) {
                continue;
            }

            $this->update(self::OLD_SITEMAPS_TABLE, [
                'type' => $type['newType'],
            ], [
                'type' => $type['oldType'],
            ], [], false);
        }
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
