<?php

namespace BarrelStrength\Sprout\forms\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;

class m211101_000004_migrate_addresses_table extends Migration
{
    public const OLD_ADDRESSES_TABLE = '{{%sprout_addresses}}';
    public const MIGRATION_ADDRESSES_TABLE = '{{%sprout_addresses_craft3}}';

    public function safeUp(): void
    {
        $cols = [
            'id',
            'elementId',
            'siteId',
            'fieldId',
            'countryCode',
            'administrativeAreaCode',
            'locality',
            'dependentLocality',
            'postalCode',
            'sortingCode',
            'address1',
            'address2',
            'dateCreated',
            'dateUpdated',
            'uid',
        ];

        if ($this->getDb()->tableExists(self::MIGRATION_ADDRESSES_TABLE)) {
            $rows = (new Query())
                ->select($cols)
                ->from([self::MIGRATION_ADDRESSES_TABLE])
                ->all();

            Craft::$app->getDb()->createCommand()
                ->batchInsert(self::OLD_ADDRESSES_TABLE, $cols, $rows)
                ->execute();
        }
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
