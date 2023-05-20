<?php

namespace BarrelStrength\Sprout\mailer\migrations;

use BarrelStrength\Sprout\mailer\migrations\helpers\MailerSchemaHelper;
use Craft;
use craft\db\Migration;
use craft\db\Table;

class m211101_000000_run_install_migration extends Migration
{
    public const MODULE_KEY = 'sprout.sprout-module-core.modules';
    public const MODULE_CLASS = 'BarrelStrength\Sprout\mailer\MailerModule';

    public const EMAILS_TABLE = '{{%sprout_emails}}';
    public const EMAIL_THEMES_TABLE = '{{%sprout_email_themes}}';
    public const MAILERS_TABLE = '{{%sprout_mailers}}';
    public const AUDIENCES_TABLE = '{{%sprout_audiences}}';
    public const SOURCE_GROUPS_TABLE = '{{%sprout_source_groups}}';
    public const SUBSCRIPTIONS_TABLE = '{{%sprout_subscriptions}}';

    public function safeUp(): void
    {
        $coreModuleSettingsKey = self::MODULE_KEY . '.' . self::MODULE_CLASS;

        $this->createTables();

        MailerSchemaHelper::insertDefaultMailerSettings();

        Craft::$app->getProjectConfig()->set($coreModuleSettingsKey, [
            'enabled' => true,
        ]);
    }

    public function createTables(): void
    {
        if (!$this->getDb()->tableExists(self::AUDIENCES_TABLE)) {
            $this->createTable(self::AUDIENCES_TABLE, [
                'id' => $this->primaryKey(),
                'elementId' => $this->integer()->notNull(),
                'type' => $this->string()->notNull(),
                'settings' => $this->text(),
                'name' => $this->string()->notNull(),
                'handle' => $this->string()->notNull(),
                'count' => $this->integer()->notNull()->defaultValue(0),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);

            $this->createIndex(null, self::AUDIENCES_TABLE, ['elementId']);
            $this->createIndex(null, self::AUDIENCES_TABLE, ['name']);
            $this->createIndex(null, self::AUDIENCES_TABLE, ['handle']);

            $this->addForeignKey(null, self::AUDIENCES_TABLE, ['elementId'], Table::ELEMENTS, ['id'], 'CASCADE', 'CASCADE');
        }

        if (!$this->getDb()->tableExists(self::SUBSCRIPTIONS_TABLE)) {
            $this->createTable(self::SUBSCRIPTIONS_TABLE, [
                'id' => $this->primaryKey(),
                'listId' => $this->integer()->notNull(),
                'itemId' => $this->integer()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);

            $this->createIndex(null, self::SUBSCRIPTIONS_TABLE, ['listId', 'itemId'], true);

            $this->addForeignKey(null, self::SUBSCRIPTIONS_TABLE, ['itemId'], Table::ELEMENTS, ['id'], 'CASCADE', 'CASCADE');
            $this->addForeignKey(null, self::SUBSCRIPTIONS_TABLE, ['listId'], self::AUDIENCES_TABLE, ['id'], 'CASCADE', 'CASCADE');
        }

        if (!$this->getDb()->tableExists(self::EMAILS_TABLE)) {
            $this->createTable(self::EMAILS_TABLE, [
                'id' => $this->primaryKey(),
                'emailType' => $this->string(),
                'subjectLine' => $this->string(),
                'preheaderText' => $this->string(),
                'defaultBody' => $this->text(),
                'fromName' => $this->string(),
                'fromEmail' => $this->string(),
                'replyToEmail' => $this->string(),
                'recipients' => $this->string(),
                'emailThemeUid' => $this->integer(),
                'mailerId' => $this->integer(),
                'mailerInstructionsSettings' => $this->text(),
                'emailTypeSettings' => $this->text(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
            // @todo - FKs, etc
        }

        // @todo - SAVE IN PROJECT CONFIG?
        if (!$this->getDb()->tableExists(self::EMAIL_THEMES_TABLE)) {
            $this->createTable(self::EMAIL_THEMES_TABLE, [
                'id' => $this->primaryKey(),
                'fieldLayoutUid' => $this->uid(),
                'name' => $this->string(),
                'type' => $this->string(),
                'htmlEmailTemplate' => $this->string(),
                'textEmailTemplate' => $this->string(),
                'copyPasteEmailTemplate' => $this->string(),
                'settings' => $this->text(),
                'sortOrder' => $this->integer(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }

        if (!$this->getDb()->tableExists(self::MAILERS_TABLE)) {
            $this->createTable(self::MAILERS_TABLE, [
                'id' => $this->primaryKey(),
                'name' => $this->string(),
                'type' => $this->string(),
                'settings' => $this->text(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);
        }
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
