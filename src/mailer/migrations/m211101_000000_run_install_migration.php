<?php

namespace BarrelStrength\Sprout\mailer\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;

class m211101_000000_run_install_migration extends Migration
{
    public const MODULE_KEY = 'sprout.sprout-module-core.modules';
    public const MODULE_CLASS = 'BarrelStrength\Sprout\mailer\MailerModule';

    public const EMAILS_TABLE = '{{%sprout_emails}}';
    public const AUDIENCES_TABLE = '{{%sprout_audiences}}';
    public const SUBSCRIPTIONS_TABLE = '{{%sprout_subscriptions}}';

    public function safeUp(): void
    {
        $coreModuleSettingsKey = self::MODULE_KEY . '.' . self::MODULE_CLASS;

        $this->createTables();

        Craft::$app->getProjectConfig()->set($coreModuleSettingsKey, [
            'enabled' => true,
        ]);
    }

    public function createTables(): void
    {
        if (!$this->getDb()->tableExists(self::AUDIENCES_TABLE)) {
            $this->createTable(self::AUDIENCES_TABLE, [
                'id' => $this->primaryKey(),
                'name' => $this->string()->notNull(),
                'handle' => $this->string()->notNull(),
                'type' => $this->string()->notNull(),
                'settings' => $this->text(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);

            $this->createIndex(null, self::AUDIENCES_TABLE, ['name']);
            $this->createIndex(null, self::AUDIENCES_TABLE, ['handle']);

            $this->addForeignKey(null, self::AUDIENCES_TABLE, ['id'], Table::ELEMENTS, ['id'], 'CASCADE', 'CASCADE');
        }

        if (!$this->getDb()->tableExists(self::SUBSCRIPTIONS_TABLE)) {
            $this->createTable(self::SUBSCRIPTIONS_TABLE, [
                'id' => $this->primaryKey(),
                'subscriberListId' => $this->integer()->notNull(),
                'userId' => $this->integer()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);

            $this->createIndex(null, self::SUBSCRIPTIONS_TABLE, ['subscriberListId', 'userId'], true);

            $this->addForeignKey(null, self::SUBSCRIPTIONS_TABLE, ['subscriberListId'], self::AUDIENCES_TABLE, ['id'], 'CASCADE', 'CASCADE');
            $this->addForeignKey(null, self::SUBSCRIPTIONS_TABLE, ['userId'], Table::ELEMENTS, ['id'], 'CASCADE', 'CASCADE');
        }

        if (!$this->getDb()->tableExists(self::EMAILS_TABLE)) {
            $this->createTable(self::EMAILS_TABLE, [
                'id' => $this->primaryKey(),
                'subjectLine' => $this->string(),
                'preheaderText' => $this->string(),
                'defaultMessage' => $this->text(),
                'emailVariantType' => $this->string(),
                'emailVariantSettings' => $this->text(),
                'mailerInstructionsSettings' => $this->text(),
                'emailTypeUid' => $this->uid(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);

            $this->createIndex(null, self::EMAILS_TABLE, ['subjectLine']);
            $this->createIndex(null, self::EMAILS_TABLE, ['emailVariantType']);
            $this->createIndex(null, self::EMAILS_TABLE, ['emailTypeUid']);

            $this->addForeignKey(null, self::EMAILS_TABLE, ['id'], Table::ELEMENTS, ['id'], 'CASCADE', 'CASCADE');
        }
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
