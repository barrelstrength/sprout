<?php

/** @noinspection DuplicatedCode */

namespace BarrelStrength\Sprout\transactional\migrations;

use BarrelStrength\Sprout\mailer\mailers\MailerHelper;
use BarrelStrength\Sprout\mailer\migrations\helpers\MailerSchemaHelper;
use BarrelStrength\Sprout\transactional\components\mailers\TransactionalMailer;
use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Json;
use craft\helpers\ProjectConfig;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;

class m211101_000001_migrate_settings_table_to_projectconfig extends Migration
{
    public const SPROUT_KEY = 'sprout';
    public const MODULES_KEY = self::SPROUT_KEY . '.sprout-module-core.modules';
    public const MODULE_ID = 'sprout-module-transactional';
    public const MODULE_CLASS = 'BarrelStrength\Sprout\transactional\TransactionalModule';
    public const EMAIL_ELEMENT_TYPE = 'BarrelStrength\Sprout\mailer\components\elements\email\EmailElement';

    public const EMAIL_MESSAGE_EMAIL_TYPE = 'BarrelStrength\Sprout\mailer\components\emailtypes\EmailMessageEmailType';
    public const FORM_SUMMARY_EMAIL_TYPE = 'BarrelStrength\Sprout\forms\components\emailtypes\FormSummaryEmailType';
    public const CUSTOM_TEMPLATES_EMAIL_TYPE = 'BarrelStrength\Sprout\mailer\components\emailtypes\CustomTemplatesEmailType';

    public const OLD_SETTINGS_MODEL = 'barrelstrength\sproutbaseemail\models\Settings';
    public const OLD_SETTINGS_TABLE = '{{%sprout_settings_craft3}}';

    public const OLD_NOTIFICATIONS_TABLE = '{{%sproutemail_notificationemails}}';

    public const CRAFT_MAILER_SETTINGS_UID = 'craft';

    public function safeUp(): void
    {
        $moduleSettingsKey = self::SPROUT_KEY . '.' . self::MODULE_ID;
        $coreModuleSettingsKey = self::MODULES_KEY . '.' . self::MODULE_CLASS;

        // Table renamed first in core migrations
        if (!$this->db->tableExists(self::OLD_SETTINGS_TABLE)) {
            return;
        }

        // Get shared sprout settings from old schema
        $oldSettings = (new Query())
            ->select(['model', 'settings'])
            ->from([self::OLD_SETTINGS_TABLE])
            ->where([
                'model' => self::OLD_SETTINGS_MODEL,
            ])
            ->one();

        $mailer = null;

        // Loop through all Notification Emails and create a Mailer using the From Name and From Email and Reply To values found
        if ($this->getDb()->tableExists(self::OLD_NOTIFICATIONS_TABLE)) {

            $mailer = new TransactionalMailer();
            $mailer->name = 'Custom Mailer';
            $mailer->uid = StringHelper::UUID();

            $emails = (new Query())
                ->select(['id', 'fromName', 'fromEmail', 'replyToEmail'])
                ->from([self::OLD_NOTIFICATIONS_TABLE])
                ->all();

            $approvedSenders = [];
            $approvedReplyToEmails = [];

            $uniqueApprovedSenders = [];
            $uniqueApprovedReplyToEmails = [];

            foreach ($emails as $email) {
                $approvedSenderEmailString = $email['fromEmail'];

                // Only add an email to the list once. This means multiple emails with different from names may not get migrated
                // and would need to be manually resolved after the migration
                if (!in_array($approvedSenderEmailString, $approvedSenders, true)) {
                    $approvedSenders[] = $approvedSenderEmailString;

                    $uniqueApprovedSenders[] = [
                        'fromName' => $email['fromName'],
                        'fromEmail' => $email['fromEmail'],
                    ];
                }

                $approvedReplyToString = $email['replyToEmail'];

                if (!in_array($approvedReplyToString, $approvedReplyToEmails, true)) {
                    $approvedReplyToEmails[] = $email['replyToEmail'];

                    $uniqueApprovedReplyToEmails[] = [
                        'replyToEmail' => $email['replyToEmail'],
                    ];
                }
            }

            $mailer->approvedSenders = $uniqueApprovedSenders;
            $mailer->approvedReplyToEmails = $uniqueApprovedReplyToEmails;

            $mailers = MailerHelper::getMailers();
            $mailers[$mailer->uid] = $mailer;
            MailerHelper::saveMailers($mailers);
        }

        $mailerUid = $mailer ? $mailer->uid : self::CRAFT_MAILER_SETTINGS_UID;

        $emailTypeMapping = [
            'barrelstrength\sproutbaseemail\emailtemplates\BasicTemplates' => self::EMAIL_MESSAGE_EMAIL_TYPE,
            'barrelstrength\sproutforms\integrations\sproutemail\emailtemplates\basic\BasicSproutFormsNotification' => self::FORM_SUMMARY_EMAIL_TYPE,
        ];

        // Prepare old settings for new settings format
        $newSettings = Json::decode($oldSettings['settings']);

        if (!empty($oldSettings)) {
            $oldEmailTemplateId = !empty($newSettings['emailTemplateId'])
                ? $newSettings['emailTemplateId']
                : null;

            // Create Email Message Email Type from global settings
            if ($matchingType = $emailTypeMapping[$oldEmailTemplateId] ?? null) {
                MailerSchemaHelper::createEmailTypeIfNoTypeExists($matchingType, [
                    'mailerUid' => self::CRAFT_MAILER_SETTINGS_UID,
                ]);
            } else {
                MailerSchemaHelper::createEmailTypeIfNoTypeExists(self::CUSTOM_TEMPLATES_EMAIL_TYPE, [
                    'name' => 'Custom Templates - Global',
                    'mailerUid' => self::CRAFT_MAILER_SETTINGS_UID,
                    'htmlEmailTemplate' => $oldEmailTemplateId,
                ]);
            }
        }

        // Create Email Message Email Type from email-specific override settings
        // Ignore 'enablePerEmailEmailTemplateIdOverride' and just migrate everything we find
        // as it seems there was a bug where Templates may have displayed as an option irregardless of this setting
        if ($this->getDb()->tableExists(self::OLD_NOTIFICATIONS_TABLE)) {
            $emails = (new Query())
                ->select(['id', 'fieldLayoutId', 'emailTemplateId'])
                ->from([self::OLD_NOTIFICATIONS_TABLE])
                ->all();

            foreach ($emails as $email) {
                // Skip pre-defined email types
                if ($emailTypeMapping[$email['emailTemplateId']] ?? null) {
                    continue;
                }

                $fieldLayoutId = !empty($email['fieldLayoutId'])
                    ? (int)$email['fieldLayoutId']
                    : null;

                $fieldLayout = [];

                if ($fieldLayoutId) {
                    $oldTabs = (new Query())
                        ->from(Table::FIELDLAYOUTTABS)
                        ->where(['layoutId' => $fieldLayoutId])
                        ->orderBy(['sortOrder' => SORT_ASC])
                        ->all();

                    if (!$oldTabs) {
                        continue;
                    }

                    $oldFieldLayoutTabIds = array_map(static function($tab) {
                        return $tab['id'];
                    }, $oldTabs);

                    $newTabs = [];
                    $newFieldLayout = new FieldLayout([
                        'type' => self::EMAIL_ELEMENT_TYPE,
                    ]);
                    foreach ($oldTabs as $fieldLayoutTab) {
                        $layoutElements = Json::decode($fieldLayoutTab['elements']);
                        if (!$layoutElements) {
                            continue;
                        }
                        $newTab = new FieldLayoutTab([
                            'layout' => $newFieldLayout,
                            'name' => $fieldLayoutTab['name'],
                        ]);
                        $newTab->setElements($layoutElements);
                        $newTabs[] = $newTab;
                    }

                    $newFieldLayout->setTabs($newTabs);
                    $fieldLayout = $newFieldLayout;

                    Craft::$app->getDb()->createCommand()
                        ->delete(Table::FIELDLAYOUTFIELDS, ['id' => $oldFieldLayoutTabIds])
                        ->execute();
                }

                $emailType = MailerSchemaHelper::createEmailTypeIfNoTypeExists(self::CUSTOM_TEMPLATES_EMAIL_TYPE, [
                    'name' => 'Custom Templates - ' . $email['emailTemplateId'],
                    'mailerUid' => $mailerUid,
                    'htmlEmailTemplate' => $email['emailTemplateId'],
                    'fieldLayout' => $fieldLayout,
                ]);

                // Set all emailTemplateId to the new Email Type UID.
                // This will be migrated in another migration and correct after the migration is complete.
                $this->update(self::OLD_NOTIFICATIONS_TABLE, [
                    'emailTemplateId' => $emailType->uid,
                ], [
                    'id' => $email['id'],
                ]);
            }
        }

        $newCoreSettings = [
            'enabled' => $newSettings['enableNotificationEmails'],
        ];

        unset(
            $newSettings['pluginNameOverride'],
            $newSettings['enableNotificationEmails'],
            $newSettings['enablePerEmailEmailTemplateIdOverride'],
            $newSettings['emailTemplateId'],
        );

        Craft::$app->getProjectConfig()->set($moduleSettingsKey, $newSettings,
            "Update Sprout Settings for “{$moduleSettingsKey}”"
        );

        Craft::$app->getProjectConfig()->set($coreModuleSettingsKey, $newCoreSettings,
            "Update Sprout Core Settings for “{$coreModuleSettingsKey}”"
        );

        $this->delete(self::OLD_SETTINGS_TABLE, ['model' => self::OLD_SETTINGS_MODEL]);

        $this->deleteSettingsTableIfEmpty();
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }

    public function deleteSettingsTableIfEmpty(): void
    {
        $oldSettings = (new Query())
            ->select('*')
            ->from([self::OLD_SETTINGS_TABLE])
            ->all();

        if (empty($oldSettings)) {
            $this->dropTableIfExists(self::OLD_SETTINGS_TABLE);
        }
    }
}
