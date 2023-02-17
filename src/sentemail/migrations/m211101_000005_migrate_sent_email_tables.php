<?php

namespace BarrelStrength\Sprout\sentemail\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;

class m211101_000005_migrate_sent_email_tables extends Migration
{
    public const SENT_EMAILS_TABLE = '{{%sprout_sent_emails}}';
    public const OLD_SENT_EMAIL_TABLE = '{{%sproutemail_sentemail}}';

    public function safeUp(): void
    {
        $oldCols = [
            'id',
            'title',
            'emailSubject AS subjectLine',
            'fromEmail',
            'fromName',
            'toEmail',
            'body AS textBody',
            'htmlBody',
            'info',
            'status',
            'dateCreated',
            'dateUpdated',
            'uid',
        ];

        $newCols = [
            'id',
            'title',
            'subjectLine',
            'fromEmail',
            'fromName',
            'toEmail',
            'textBody',
            'htmlBody',
            'info',
            'status',
            'dateCreated',
            'dateUpdated',
            'uid',
        ];

        // @todo - make a command line utility to migrate these, or a config setting to exclude them?
        if ($this->getDb()->tableExists(self::OLD_SENT_EMAIL_TABLE)) {
            $rows = (new Query())
                ->select($oldCols)
                ->from([self::OLD_SENT_EMAIL_TABLE])
                ->all();

            Craft::$app->getDb()->createCommand()
                ->batchInsert(self::SENT_EMAILS_TABLE, $newCols, $rows)
                ->execute();
        }
    }

    public function safeDown(): bool
    {
        echo "m211101_000005_migrate_sent_email_tables cannot be reverted.\n";

        return false;
    }
}
