<?php

namespace BarrelStrength\Sprout\transactional\migrations;

use BarrelStrength\Sprout\mailer\components\emailthemes\CustomEmailTheme;
use Craft;
use craft\db\Migration;
use craft\db\Query;
use Psy\Util\Json;

class m211101_000006_migrate_notifications_tables extends Migration
{
    public const EMAIL_CLASS = 'BarrelStrength\Sprout\transactional\components\elements\TransactionalEmailElement';
    public const NEW_EMAIL_TABLE = '{{%sprout_emails}}';
    public const NEW_EMAIL_THEMES_TABLE = '{{%sprout_email_themes}}';
    public const OLD_NOTIFICATIONS_TABLE = '{{%sproutemail_notificationemails}}';

    public function safeUp(): void
    {
        $oldThemeCols = [
            'id', // grab for mapping but don't migrate
            'emailTemplateId',
        ];

        $themeCols = [
            'name',
            'type',
            'htmlEmailTemplate',
            'copyPasteEmailTemplate',
            'settings',
            'sortOrder',
        ];

        // @todo - refactor to correct columns. Lots of changes and need to also
        // consider how to migrate campaign-specific stuff, send rule, field layout...
        //        $cols = [
        //            'id',
        //            'titleFormat',
        //            'emailTemplateId',
        //            'eventId',
        //            'settings',
        //            'sendRule',
        //            'subjectLine',
        //            'defaultMessage',
        //            'recipients',
        //            'cc',
        //            'bcc',
        //            'listSettings',
        //            'fromName',
        //            'fromEmail',
        //            'replyToEmail',
        //            'sendMethod',
        //            'enableFileAttachments',
        //            'dateCreated',
        //            'dateUpdated',
        //            'fieldLayoutId',
        //            'uid',
        //        ];

        $oldEmailCols = [
            'id',
            'subjectLine',
            'fromName',
            'fromEmail',
            'replyToEmail',
            'recipients',
            'dateCreated',
            'dateUpdated',
            'uid',

            'defaultBody', // Remap to defaultMessage
            'emailTemplateId', // Remap to emailThemeUid
            'preheaderText', // Hard code default
            'emailType', // Hard code as 'notification' ?
        ];

        $emailCols = [
            'id',
            'subjectLine',
            'dateCreated',
            'dateUpdated',
            'uid',

            'defaultMessage',
            'emailThemeUid',
            'preheaderText', // Hard code default
            'emailType', // Hard code as 'notification' ?
            'mailerInstructionsSettings',
        ];

        if ($this->getDb()->tableExists(self::OLD_NOTIFICATIONS_TABLE)) {

            $themeRows = (new Query())
                ->select($oldThemeCols)
                ->from([self::OLD_NOTIFICATIONS_TABLE])
                ->all();

            $themeRowsMapped = [];
            $sortOrder = 0;

            foreach ($themeRows as $key => $themeRow) {

                // If old setting is a classname, use it, if a path, set to CustomEmailTheme::class
                //                if ($themeRow['emailTemplateId'] == )

                // @todo fix all this logic... hard coded and wrong at the moment
                //                $emailTheme = CustomEmailTheme::class;

                $themeRowsMapped[$key] = [
                    'name' => CustomEmailTheme::displayName(),
                    'type' => CustomEmailTheme::class,

                    // Check if custom themes are using same template values, may have multiple themes...
                    'htmlEmailTemplate' => $themeRow['emailTemplateId'],
                    'copyPasteEmailTemplate' => $themeRow['emailTemplateId'],
                    'settings' => '',
                    'sortOrder' => $sortOrder++,
                ];
                //                $themeMapping[$themeRow['id']] = $themeRow['emailTemplateId'];
            }

            Craft::$app->getDb()->createCommand()
                ->batchInsert(self::NEW_EMAIL_THEMES_TABLE, $themeCols, $themeRowsMapped)
                ->execute();

            $rows = (new Query())
                ->select(array_diff($oldEmailCols, ['preheaderText', 'emailType']))
                ->from([self::OLD_NOTIFICATIONS_TABLE])
                ->all();

            foreach ($rows as $key => $value) {
                $rows[$key]['defaultMessage'] = $rows[$key]['defaultBody'];
                unset($rows[$key]['defaultBody']);

                // @todo - figure out how we store custom template data...
                $rows[$key]['emailThemeId'] = 1;
                unset($rows[$key]['emailTemplateId']);

                $rows[$key]['preheaderText'] = '';
                $rows[$key]['emailType'] = self::EMAIL_CLASS;

                $rows[$key]['mailerInstructionsSettings'] = Json::encode([
                    'fromName' => $rows[$key]['fromName'],
                    'fromEmail' => $rows[$key]['fromEmail'],
                    'replyToEmail' => $rows[$key]['replyToEmail'],
                    'recipients' => $rows[$key]['recipients'],
                ]);
                unset(
                    $rows[$key]['fromName'],
                    $rows[$key]['fromEmail'],
                    $rows[$key]['replyToEmail'],
                    $rows[$key]['recipients']
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
}
