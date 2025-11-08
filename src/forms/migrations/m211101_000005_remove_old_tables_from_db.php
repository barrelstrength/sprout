<?php

namespace BarrelStrength\Sprout\forms\migrations;

use craft\db\Migration;

/**
 * @role temporary: Craft 4 => 5
 * @schema sprout-module-forms
 */
#[\Deprecated('tag:craftcms/cms:6.0', 'migration will be removed in Craft CMS 6.0')]
class m211101_000005_remove_old_tables_from_db extends Migration
{
    public const MIGRATION_ADDRESSES_TABLE = '{{%sprout_addresses_craft3}}';

    public function safeUp(): void
    {
        $this->dropTableIfExists(self::MIGRATION_ADDRESSES_TABLE);
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
