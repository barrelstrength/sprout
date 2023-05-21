<?php

namespace BarrelStrength\Sprout\sentemail\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;

class m211101_000000_run_install_migration extends Migration
{
    public const SPROUT_KEY = 'sprout';
    public const MODULES_KEY = self::SPROUT_KEY . '.sprout-module-core.modules';
    public const MODULE_ID = 'sprout-module-sent-email';
    public const MODULE_CLASS = 'BarrelStrength\Sprout\sentemail\SentEmailModule';
    public const SENT_EMAILS_TABLE = '{{%sprout_sent_emails}}';

    public function safeUp(): void
    {
        $moduleSettingsKey = self::SPROUT_KEY . '.' . self::MODULE_ID;
        $coreModuleSettingsKey = self::MODULES_KEY . '.' . self::MODULE_CLASS;

        $this->createTables();

        Craft::$app->getProjectConfig()->set($moduleSettingsKey, [
            'sentEmailsLimit' => 2500,
            'cleanupProbability' => 1000,
        ], "Update Sprout CP Settings for “{$moduleSettingsKey}”");

        Craft::$app->getProjectConfig()->set($coreModuleSettingsKey, [
            'enabled' => true,
        ]);
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }

    public function createTables(): void
    {
        if (!$this->getDb()->tableExists(self::SENT_EMAILS_TABLE)) {
            $this->createTable(self::SENT_EMAILS_TABLE, [
                'id' => $this->primaryKey(),
                'title' => $this->string(),
                'subjectLine' => $this->string(),
                'fromEmail' => $this->string(),
                'fromName' => $this->string(),
                'toEmail' => $this->string(),
                'textBody' => $this->mediumText(),
                'htmlBody' => $this->mediumText(),
                'info' => $this->text(),
                'sent' => $this->boolean()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);

            $this->createIndex(null, self::SENT_EMAILS_TABLE, ['title']);
            $this->createIndex(null, self::SENT_EMAILS_TABLE, ['subjectLine']);
            $this->createIndex(null, self::SENT_EMAILS_TABLE, ['toEmail']);

            $this->addForeignKey(null, self::SENT_EMAILS_TABLE, ['id'], Table::ELEMENTS, ['id'], 'CASCADE', 'CASCADE');
        }
    }
}
