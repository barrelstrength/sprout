<?php

namespace BarrelStrength\Sprout\redirects\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;

class m211101_000005_migrate_redirects_tables extends Migration
{
    public const REDIRECTS_TABLE = '{{%sprout_redirects}}';
    public const OLD_REDIRECTS_TABLE = '{{%sproutseo_redirects}}';

    public function safeUp(): void
    {
        $oldCols = [
            'id',
            'oldUrl',
            'newUrl',
            'method AS statusCode',
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
                ->all();

            Craft::$app->getDb()->createCommand()
                ->batchInsert(self::REDIRECTS_TABLE, $newCols, $rows)
                ->execute();
        }
    }

    public function safeDown(): bool
    {
        echo "m211101_000005_migrate_redirects_tables cannot be reverted.\n";

        return false;
    }
}
