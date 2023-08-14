<?php

namespace BarrelStrength\Sprout\transactional\migrations;

use BarrelStrength\Sprout\mailer\mailers\MailerHelper;
use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

class m211101_000006_migrate_notifications_tables extends Migration
{
    public const TRANSACTIONAL_EMAIL_ELEMENT_TYPE = 'BarrelStrength\Sprout\transactional\components\elements\TransactionalEmailElement';
    public const NEW_EMAIL_TABLE = '{{%sprout_emails}}';
    public const OLD_NOTIFICATIONS_TABLE = '{{%sproutemail_notificationemails}}';

    public function safeUp(): void
    {
        $oldEmailCols = [
            'id',
            'subjectLine',
            'defaultBody as defaultMessage', // => defaultMessage
            'emailTemplateId as emailThemeUid', // Already migrated to emailThemeUid
            'dateCreated',
            'dateUpdated',
            'uid',

            // Mailer
            'fromName', // => Approved Senders && mailerInstructionsSettings
            'fromEmail', // => Approved Senders && mailerInstructionsSettings
            'replyToEmail', // => Approved Reply To && mailerInstructionsSettings
            'recipients', // => mailerInstructionsSettings
            'cc', // Merge into recipients
            'bcc', // => sendMethod
            'listSettings', // => mailerInstructionsSettings Audience?
            //'sendMethod', // no need to migrate, standardized to use List Method

            // Email Type: Transactional, Notification Event Settings
            'eventId',
            'settings',
            'sendRule',
            'enableFileAttachments',
        ];

        $emailCols = [
            'id',
            'subjectLine',
            'defaultMessage',
            'emailThemeUid',
            'dateCreated',
            'dateUpdated',
            'uid',

            //'preheaderText', // No need to migrate, new setting

            // Email Type: Transactional
            'type',
            'emailTypeSettings',

            // Mailer: Transactional Mailer
            'mailerUid',
            'mailerInstructionsSettings',
        ];

        if ($this->getDb()->tableExists(self::OLD_NOTIFICATIONS_TABLE)) {

            $defaultMailer = MailerHelper::getDefaultMailer();

            $rows = (new Query())
                ->select($oldEmailCols)
                ->from([self::OLD_NOTIFICATIONS_TABLE])
                ->all();

            foreach ($rows as $key => $value) {

                $rows[$key]['type'] = self::TRANSACTIONAL_EMAIL_ELEMENT_TYPE;

                //'eventId',
                //'settings',
                //'sendRule',
                //'enableFileAttachments',

                $eventId = $rows[$key]['eventId'];
                $oldEventSettings = Json::decode($rows[$key]['settings'] ?? '[]');
                $sendRule = $rows[$key]['sendRule'];
                $eventSettings = $this->prepareEventSettings($eventId, $oldEventSettings, $sendRule);

                $rows[$key]['emailTypeSettings'] = Json::encode([
                    'eventId' => $rows[$key]['eventId'],
                    'eventSettings' => $eventSettings,
                    'enableFileAttachments' => $rows[$key]['enableFileAttachments'] ?? '',
                ]);

                // merge bcc into recipients if cc not empty
                $recipients = $rows[$key]['recipients'] ?? '';
                $cc = $rows[$key]['cc'] ?? '';
                if (!empty($cc)) {
                    $recipients .= ',' . $cc;
                }
                $bcc = $rows[$key]['bcc'] ?? '';
                if (!empty($bcc)) {
                    $recipients .= ',' . $bcc;
                }

                $sender = $rows[$key]['fromName'] . ' <' . $rows[$key]['fromEmail'] . '>';

                $listSettings = Json::decode($rows[$key]['listSettings'] ?? '[]');
                $audienceIds = $listSettings['listIds'] ?? [];

                $rows[$key]['mailerUid'] = $defaultMailer->uid;
                $rows[$key]['mailerInstructionsSettings'] = Json::encode([
                    'sender' => $sender,
                    'replyToEmail' => $rows[$key]['replyToEmail'],
                    'recipients' => trim($recipients),
                    'audienceIds' => $audienceIds,
                ]);

                unset(
                    $rows[$key]['preheaderText'], // No need to migrate, new setting
                    $rows[$key]['fieldLayoutId'], // Migrated when CustomTemplateEmailTheme created
                    $rows[$key]['fromName'],
                    $rows[$key]['fromEmail'],
                    $rows[$key]['replyToEmail'],
                    $rows[$key]['recipients'],
                    $rows[$key]['cc'],
                    $rows[$key]['bcc'],
                    $rows[$key]['listSettings'],
                );
            }

            Craft::$app->getDb()->createCommand()
                ->batchInsert(self::NEW_EMAIL_TABLE, $emailCols, $rows)
                ->execute();
        }
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }

    public function prepareEventSettings($eventId, $oldEventSettings, $sendRule): array
    {
}
