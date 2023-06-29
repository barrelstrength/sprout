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
            'old_redirects_table.id AS id',
            'old_redirects_table.oldUrl AS oldUrl',
            'old_redirects_table.newUrl AS newUrl',
            'old_redirects_table.method AS statusCode',
            'old_redirects_table.matchStrategy AS matchStrategy',
            'old_redirects_table.count AS count',
            'old_redirects_table.lastRemoteIpAddress AS lastRemoteIpAddress',
            'old_redirects_table.lastReferrer AS lastReferrer',
            'old_redirects_table.lastUserAgent AS lastUserAgent',
            'old_redirects_table.dateLastUsed AS dateLastUsed',
            'old_redirects_table.dateCreated AS dateCreated',
            'old_redirects_table.dateUpdated AS dateUpdated',
            'old_redirects_table.uid AS uid',
            'elements_sites.siteId AS siteId',
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
                ->from(['old_redirects_table' => self::OLD_REDIRECTS_TABLE])
                ->where(['in', 'old_redirects_table.method', ['301', '302']])
                ->innerJoin(['elements_sites' => Table::ELEMENTS_SITES],
                    '[[old_redirects_table.id]] = [[elements_sites.elementId]]')
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
