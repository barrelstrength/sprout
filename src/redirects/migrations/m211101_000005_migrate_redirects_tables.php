<?php

namespace BarrelStrength\Sprout\redirects\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\StringHelper;

class m211101_000005_migrate_redirects_tables extends Migration
{
    public const REDIRECTS_TABLE = '{{%sprout_redirects}}';
    public const OLD_REDIRECTS_TABLE = '{{%sproutseo_redirects}}';

    public function safeUp(): void
    {
        $oldCols = [
            '[[sproutseo_redirects.id]] AS id',
            '[[sproutseo_redirects.oldUrl]] AS oldUrl',
            '[[sproutseo_redirects.newUrl]] AS newUrl',
            '[[sproutseo_redirects.method]] AS statusCode',
            '[[sproutseo_redirects.matchStrategy]] AS matchStrategy',
            '[[sproutseo_redirects.count]] AS count',
            '[[sproutseo_redirects.lastRemoteIpAddress]] AS lastRemoteIpAddress',
            '[[sproutseo_redirects.lastReferrer]] AS lastReferrer',
            '[[sproutseo_redirects.lastUserAgent]] AS lastUserAgent',
            '[[sproutseo_redirects.dateLastUsed]] AS dateLastUsed',
            '[[sproutseo_redirects.dateCreated]] AS dateCreated',
            '[[sproutseo_redirects.dateUpdated]] AS dateUpdated',
            '[[sproutseo_redirects.uid]] AS uid',
            '[[elements_sites.siteId]] AS siteId',
        ];

        $newCols = [
            'id',
            'oldUrl',
            'newUrl',
            'statusCode',
            'matchStrategy',
            'count',
            'lastRemoteIpAddress',
            'lastReferrer',
            'lastUserAgent',
            'dateLastUsed',
            'dateCreated',
            'dateUpdated',
            'uid',
        ];

        if ($this->getDb()->tableExists(self::OLD_REDIRECTS_TABLE)) {
            // Only migrate 301, 302 redirects. 404s can be converted before migration.
            $rows = (new Query())
                ->select($oldCols)
                ->from([self::OLD_REDIRECTS_TABLE])
                ->where(['in', 'method', ['301', '302']])
                ->innerJoin(
                    Table::ELEMENTS_SITES,
                    '[[sproutseo_redirects.id]] = [[elements_sites.elementId]]'
                )
                ->all();

            foreach ($rows as $key => $row) {

                $now = Db::prepareDateForDb(DateTimeHelper::now());
                
                // Create a row in the content table for each element to support custom fields
                $this->insert(Table::CONTENT, [
                    'elementId' => $row['id'],
                    'siteId' => $row['siteId'],
                    'dateCreated' => $now,
                    'dateUpdated' => $now,
                    'uid' => StringHelper::UUID(),
                ]);

                unset($rows[$key]['siteId']);
            }

            Craft::$app->getDb()->createCommand()
                ->batchInsert(self::REDIRECTS_TABLE, $newCols, $rows)
                ->execute();
        }
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
