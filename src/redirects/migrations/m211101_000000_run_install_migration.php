<?php

namespace BarrelStrength\Sprout\redirects\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\errors\ElementNotFoundException;
use craft\records\Structure;

/**
 * @role permanent
 * @schema sprout-module-redirects
 * @since 4.0.0
 */
class m211101_000000_run_install_migration extends Migration
{
    public const SPROUT_KEY = 'sprout';
    public const MODULES_KEY = self::SPROUT_KEY . '.sprout-module-core.modules';
    public const MODULE_ID = 'sprout-module-redirects';
    public const MODULE_CLASS = 'BarrelStrength\Sprout\redirects\RedirectsModule';
    public const REDIRECTS_TABLE = '{{%sprout_redirects}}';
    public const SETTINGS_TABLE = '{{%sprout_settings}}';

    public const EXACT_MATCH = 'exactMatch';
    public const REGEX_MATCH = 'regExMatch';
    public const URL_WITHOUT_QUERY_STRINGS = 'urlWithoutQueryStrings';
    public const REMOVE_QUERY_STRINGS = 'removeQueryStrings';

    public function safeUp(): void
    {
        $this->createTables();
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }

    public function createTables(): void
    {
        if (!$this->db->tableExists(self::REDIRECTS_TABLE)) {
            $this->createTable(self::REDIRECTS_TABLE, [
                'id' => $this->primaryKey(),
                'oldUrl' => $this->string()->defaultValue(null),
                'newUrl' => $this->string()->defaultValue(null),
                'statusCode' => $this->integer()->notNull(),
                'matchStrategy' => $this->enum('matchStrategy', [
                    self::EXACT_MATCH,
                    self::REGEX_MATCH,
                    self::URL_WITHOUT_QUERY_STRINGS,
                ])->notNull()->defaultValue(self::EXACT_MATCH),
                'count' => $this->integer()->defaultValue(0),
                'lastRemoteIpAddress' => $this->string()->defaultValue(null),
                'lastReferrer' => $this->string()->defaultValue(null),
                'lastUserAgent' => $this->string()->defaultValue(null),
                'dateLastUsed' => $this->dateTime()->defaultValue(null),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);

            $this->createIndex(null, self::REDIRECTS_TABLE, ['oldUrl']);
            $this->createIndex(null, self::REDIRECTS_TABLE, ['newUrl']);
            $this->createIndex(null, self::REDIRECTS_TABLE, ['statusCode']);
            $this->createIndex(null, self::REDIRECTS_TABLE, ['count']);

            $this->addForeignKey(null, self::REDIRECTS_TABLE, ['id'], Table::ELEMENTS, ['id'], 'CASCADE', 'CASCADE');
        }
    }
}
