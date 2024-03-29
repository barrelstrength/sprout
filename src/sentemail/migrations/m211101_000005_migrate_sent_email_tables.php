<?php

namespace BarrelStrength\Sprout\sentemail\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

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
            'dateCreated',
            'dateUpdated',
            'uid',

            'status',
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
            'dateCreated',
            'dateUpdated',
            'uid',

            'sent',
        ];

        if ($this->getDb()->tableExists(self::OLD_SENT_EMAIL_TABLE)) {
            $rows = (new Query())
                ->select($oldCols)
                ->from([self::OLD_SENT_EMAIL_TABLE])
                ->all();

            foreach ($rows as $key => $row) {
                $rows[$key]['sent'] = $rows[$key]['status'] === 'sent';
                unset($rows[$key]['status']);

                $info = Json::decodeIfJson($rows[$key]['info']);
                $info['deliveryStatus'] = $info['deliveryType'] ?? null;
                $info['transportType'] = $info['mailer'] ?? null;

                unset(
                    $info['emailType'],
                    $info['deliveryType'],
                    $info['source'],
                    $info['sourceVersion'],
                    $info['mailer'],
                    $info['hostName'],
                    $info['smtpSecureTransportType'],
                    $info['timeout'],
                    $info['protocol'],
                    $info['port'],
                    $info['host'],
                    $info['username'],
                    $info['encryptionMethod'],
                );
                $rows[$key]['info'] = Json::encode($info);
            }

            Craft::$app->getDb()->createCommand()
                ->batchInsert(self::SENT_EMAILS_TABLE, $newCols, $rows)
                ->execute();
        }
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
