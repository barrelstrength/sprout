<?php

namespace BarrelStrength\Sprout\meta\migrations\helpers;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;

class InsertDefaultMetadata extends Migration
{
    public const GLOBAL_METADATA_TABLE = '{{%sprout_global_metadata}}';

    public function safeUp(): void
    {
        $siteIds = (new Query())
            ->select(['id'])
            ->from(Table::SITES)
            ->column();

        $defaultSettings = '{
            "metaDivider":"-",
            "defaultOgType":"website",
            "ogTransform":"sprout-socialSquare",
            "twitterTransform":"sprout-socialSquare",
            "defaultTwitterCard":"summary",
            "appendTitleValueOnHomepage":"",
            "appendTitleValue": ""}
            ';

        foreach ($siteIds as $siteId) {
            // Code copied from GlobalMetadata::insertDefaultGlobalMetadata()
            Craft::$app->getDb()->createCommand()->insert(self::GLOBAL_METADATA_TABLE, [
                'siteId' => $siteId,
                'identity' => null,
                'ownership' => null,
                'contacts' => null,
                'social' => null,
                'robots' => null,
                'settings' => $defaultSettings,
            ])->execute();
        }
    }

    public function safeDown(): bool
    {
        return false;
    }
}
