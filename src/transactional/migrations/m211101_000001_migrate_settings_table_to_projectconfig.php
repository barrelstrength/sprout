<?php

/** @noinspection DuplicatedCode */

namespace BarrelStrength\Sprout\transactional\migrations;

use BarrelStrength\Sprout\forms\components\emailtypes\FormSummaryEmailType;
use BarrelStrength\Sprout\mailer\components\emailtypes\CustomTemplatesEmailType;
use BarrelStrength\Sprout\mailer\components\emailtypes\EmailMessageEmailType;
use BarrelStrength\Sprout\mailer\migrations\helpers\MailerSchemaHelper;
use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Json;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;

class m211101_000001_migrate_settings_table_to_projectconfig extends Migration
{
    public const SPROUT_KEY = 'sprout';
    public const MODULES_KEY = self::SPROUT_KEY . '.sprout-module-core.modules';
    public const MODULE_ID = 'sprout-module-transactional';
    public const MODULE_CLASS = 'BarrelStrength\Sprout\transactional\TransactionalModule';
    public const EMAIL_ELEMENT_TYPE = 'BarrelStrength\Sprout\mailer\components\elements\email\EmailElement';
    public const OLD_SETTINGS_MODEL = 'barrelstrength\sproutbaseemail\models\Settings';
    public const OLD_SETTINGS_TABLE = '{{%sprout_settings_craft3}}';

    public const OLD_NOTIFICATIONS_TABLE = '{{%sproutemail_notificationemails}}';

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

        if (empty($oldSettings)) {
            Craft::warning('No shared settings found to migrate: ' . self::MODULE_ID);

            return;
        }

        // Prepare old settings for new settings format
        $newSettings = Json::decode($oldSettings['settings']);

        $oldEmailTemplateId = !empty($newSettings['emailTemplateId'])
            ? $newSettings['emailTemplateId']
            : null;

        $emailTypeMapping = [
            'barrelstrength\sproutbaseemail\emailtemplates\BasicTemplates' => EmailMessageEmailType::class,
            'barrelstrength\sproutforms\integrations\sproutemail\emailtemplates\basic\BasicSproutFormsNotification' => FormSummaryEmailType::class,
        ];

        // Create Email Message Email Type from global settings
        if ($matchingType = $emailTypeMapping[$oldEmailTemplateId] ?? null) {
            MailerSchemaHelper::createEmailTypeIfNoTypeExists($matchingType);
        } else {
            MailerSchemaHelper::createEmailTypeIfNoTypeExists(CustomTemplatesEmailType::class, [
                'name' => 'Custom Templates',
                'htmlEmailTemplate' => $oldEmailTemplateId,
            ]);
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
                    ? $email['fieldLayoutId']
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
                        $newTab = new FieldLayoutTab([
                            'layout' => $newFieldLayout,
                            'name' => $fieldLayoutTab['name'],
                        ]);
                        $layoutElements = Json::decode($fieldLayoutTab['elements']);
                        $newTab->setElements($layoutElements);
                        $newTabs[] = $newTab;
                    }

                    $newFieldLayout->setTabs($newTabs);
                    $fieldLayout = $newFieldLayout;

                    Craft::$app->getDb()->createCommand()
                        ->delete(Table::FIELDLAYOUTFIELDS, ['id' => $oldFieldLayoutTabIds])
                        ->execute();
                }

                $emailType = MailerSchemaHelper::createEmailTypeIfNoTypeExists(CustomTemplatesEmailType::class, [
                    'name' => 'Custom Templates',
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
