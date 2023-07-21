<?php

namespace BarrelStrength\Sprout\transactional\migrations;

use BarrelStrength\Sprout\mailer\components\emailthemes\CustomEmailTheme;
use Craft;
use craft\db\Migration;
use craft\db\Query;

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
        //            'defaultBody',
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

        $cols2 = [
            'id',
            'subjectLine',
            'defaultBody',
            'fromName',
            'fromEmail',
            'replyToEmail',
            'recipients',
            'dateCreated',
            'dateUpdated',
            'uid',

            'emailTemplateId', // Remap to emailThemeUid
            'preheaderText', // Hard code default
            'emailType', // Hard code as 'notification' ?
        ];

        $cols3 = [
            'id',
            'subjectLine',
            'defaultBody',
            'fromName',
            'fromEmail',
            'replyToEmail',
            'recipients',
            'dateCreated',
            'dateUpdated',
            'uid',

            'emailThemeUid',
            'preheaderText', // Hard code default
            'emailType', // Hard code as 'notification' ?
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
                ->select(array_diff($cols2, ['preheaderText', 'emailType']))
                ->from([self::OLD_NOTIFICATIONS_TABLE])
                ->all();

            foreach ($rows as $key => $value) {
                // @todo - figure out how we store custom template data...
                $rows[$key]['emailThemeId'] = 1;
                unset($rows[$key]['emailTemplateId']);

                $rows[$key]['preheaderText'] = '';
                $rows[$key]['emailType'] = self::EMAIL_CLASS;
            }

            Craft::$app->getDb()->createCommand()
                ->batchInsert(self::NEW_EMAIL_TABLE, $cols3, $rows)
                ->execute();
        }
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
