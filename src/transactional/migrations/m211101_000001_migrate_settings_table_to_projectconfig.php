<?php

/** @noinspection DuplicatedCode */

namespace BarrelStrength\Sprout\transactional\migrations;

use BarrelStrength\Sprout\mailer\components\emailtypes\EmailMessageEmailType;
use BarrelStrength\Sprout\mailer\mailers\MailerHelper;
use BarrelStrength\Sprout\mailer\migrations\helpers\MailerSchemaHelper;
use BarrelStrength\Sprout\transactional\components\mailers\TransactionalMailer;
use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\App;
use craft\helpers\Json;
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

    public const SENDER_BEHAVIOR_CRAFT = 'craft';
    public const SENDER_BEHAVIOR_CUSTOM = 'custom';

    public function safeUp(): void
    {
        $moduleSettingsKey = self::SPROUT_KEY . '.' . self::MODULE_ID;
        $coreModuleSettingsKey = self::MODULES_KEY . '.' . self::MODULE_CLASS;

        // Table renamed first in core migrations
        if (!$this->db->tableExists(self::OLD_SETTINGS_TABLE)) {
            return;
        }

        $mailerUids = [];

        // Loop through all Notification Emails and create a Mailer using the From Name and From Email and Reply To values found
        if ($this->getDb()->tableExists(self::OLD_NOTIFICATIONS_TABLE)) {

            $emails = (new Query())
                ->select(['id', 'fromName', 'fromEmail', 'replyToEmail', 'emailTemplateId', 'fieldLayoutId'])
                ->from([self::OLD_NOTIFICATIONS_TABLE])
                ->all();

            $customSenders = [];

            foreach ($emails as $email) {
                $fromName = $email['fromName'];
                $fromEmail = $email['fromEmail'];
                $replyToEmail = $email['replyToEmail'];

                $uniqueSenderHash = md5($fromName . $fromEmail . $replyToEmail);

                $customSenders[$uniqueSenderHash] = [
                    'elementId' => $email['id'],
                    'fromName' => $fromName,
                    'fromEmail' => $fromEmail,
                    'replyToEmail' => $replyToEmail,
                ];
            }

            $mailers = MailerHelper::getMailers();

            // create a single mailer for each combination of fromName, fromEmail, and replyToEmail
            foreach ($customSenders as $hash => $customSender) {

                $mailSettings = App::mailSettings();

                $senderEditBehavior = self::SENDER_BEHAVIOR_CUSTOM;

                if (App::parseEnv($mailSettings->fromName) === $customSender['fromName'] &&
                    App::parseEnv($mailSettings->fromEmail) === $customSender['fromEmail'] &&
                    App::parseEnv($mailSettings->replyToEmail) === $customSender['replyToEmail']) {
                    $senderEditBehavior = self::SENDER_BEHAVIOR_CRAFT;
                }

                $mailer = new TransactionalMailer();
                $mailer->uid = StringHelper::UUID();
                $mailer->name = 'Custom Mailer: ' . substr($hash, 0, 5);
                $mailer->senderEditBehavior = $senderEditBehavior;

                if ($senderEditBehavior === self::SENDER_BEHAVIOR_CUSTOM) {
                    $mailer->defaultFromName = $customSender['fromName'];
                    $mailer->defaultFromEmail = $customSender['fromEmail'];
                    $mailer->defaultReplyToEmail = $customSender['replyToEmail'];
                }

                $mailers[$mailer->uid] = $mailer;

                // Create a mapping of the Element IDs to their respective mailer and OLD template settings
                $mailerUids[$customSender['elementId']] = $mailer->uid;
            }

            MailerHelper::saveMailers($mailers);

            $emailTypeMapping = [
                'barrelstrength\sproutbaseemail\emailtemplates\BasicTemplates' => self::EMAIL_MESSAGE_EMAIL_TYPE,
                'barrelstrength\sproutforms\integrations\sproutemail\emailtemplates\basic\BasicSproutFormsNotification' => self::FORM_SUMMARY_EMAIL_TYPE,
            ];

            // Loop through the emails again, after we've prepared the Mailer UIDs

            // Create Email Message Email Type from email-specific override settings
            // Ignore 'enablePerEmailEmailTemplateIdOverride' and just migrate everything we find
            // as it seems there was a bug where Templates may have displayed as an option irregardless of this setting
            foreach ($emails as $email) {

                // emailTemplateId
                // emailId => mailerUid
                // fieldLayout ?

                // Unique sender configurations override default configurations
                $mailerUid = $mailerUids[$email['id']] ?? self::CRAFT_MAILER_SETTINGS_UID;

                // Assume no known email type, yet
                $emailType = null;

                // Updated pre-defined email types
                if ($matchingType = $emailTypeMapping[$email['emailTemplateId']] ?? null) {
                    // This should just retrieve the Email Type as it should already exists from the install migration
                    $emailType = MailerSchemaHelper::createEmailTypeIfNoTypeExists($matchingType, [
                        'mailerUid' => $mailerUid,
                    ]);

                    // No need to go further. Pre-defined Email Types define their own layouts
                    $this->update(self::OLD_NOTIFICATIONS_TABLE, [
                        'emailTemplateId' => $emailType->uid,
                    ], [
                        'id' => $email['id'],
                    ]);
                    continue;
                }

                // Then see if we have a custom layout and need to create a new Email Type
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

                if (!$emailType) {
                    // create or match existing email type
                    $emailType = MailerSchemaHelper::createEmailTypeIfNoTypeExists(self::CUSTOM_TEMPLATES_EMAIL_TYPE, [
                        'name' => 'Email Type - ' . $email['emailTemplateId'],
                        'mailerUid' => $mailerUid,
                        'htmlEmailTemplate' => $email['emailTemplateId'],
                        'fieldLayout' => $fieldLayout,
                    ], [
                        'mailerUid' => $mailerUid,
                        'htmlEmailTemplate' => $email['emailTemplateId'],
                    ]);
                }


                // Set all emailTemplateId to the new Email Type UID.
                // This will be migrated in another migration and correct after the migration is complete.
                $this->update(self::OLD_NOTIFICATIONS_TABLE, [
                    'emailTemplateId' => $emailType->uid,
                ], [
                    'id' => $email['id'],
                ]);
            }
        }

        // Get shared sprout settings from old schema
        // No need to migrate Global template settings because we're looping through
        // all email settings to determine what email types to create
        $oldSettings = (new Query())
            ->select(['model', 'settings'])
            ->from([self::OLD_SETTINGS_TABLE])
            ->where([
                'model' => self::OLD_SETTINGS_MODEL,
            ])
            ->one();

        // Prepare old settings for new settings format
        $newSettings = Json::decode($oldSettings['settings']);

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
