<?php

namespace BarrelStrength\Sprout\transactional\migrations;

use BarrelStrength\Sprout\forms\components\emailtypes\FormSummaryEmailType;
use BarrelStrength\Sprout\mailer\components\emailtypes\EmailMessageEmailType;
use BarrelStrength\Sprout\mailer\migrations\helpers\MailerSchemaHelper;
use Craft;
use craft\db\Migration;

class m211101_000000_run_install_migration extends Migration
{
    public const SPROUT_KEY = 'sprout';
    public const MODULES_KEY = self::SPROUT_KEY . '.sprout-module-core.modules';
    public const MODULE_ID = 'sprout-module-transactional';
    public const MODULE_CLASS = 'BarrelStrength\Sprout\transactional\TransactionalModule';
    public const DEFAULT_EMAIL_TYPE = 'BarrelStrength\Sprout\mailer\components\emailtypes';

    public const CRAFT_MAILER_SETTINGS_UID = 'craft';

    public function safeUp(): void
    {
        $coreModuleSettingsKey = self::MODULES_KEY . '.' . self::MODULE_CLASS;

        MailerSchemaHelper::createEmailTypeIfNoTypeExists(
            EmailMessageEmailType::class, [
            'name' => 'Email Message',
            'mailerUid' => self::CRAFT_MAILER_SETTINGS_UID,
        ]);

        // Check if plugin is installed:
        $sproutFormsIsInstalled = Craft::$app->getPlugins()->isPluginInstalled('sprout-forms');

        if ($sproutFormsIsInstalled) {
            MailerSchemaHelper::createEmailTypeIfNoTypeExists(
                FormSummaryEmailType::class, [
                'name' => 'Form Summary',
                'mailerUid' => self::CRAFT_MAILER_SETTINGS_UID,
            ]);
        }

        Craft::$app->getProjectConfig()->set($coreModuleSettingsKey, [
            'enabled' => true,
        ]);
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
