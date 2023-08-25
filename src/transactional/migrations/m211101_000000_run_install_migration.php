<?php

namespace BarrelStrength\Sprout\transactional\migrations;

use BarrelStrength\Sprout\forms\components\emailtypes\FormSummaryEmailType;
use BarrelStrength\Sprout\mailer\components\emailtypes\EmailMessageEmailType;
use BarrelStrength\Sprout\mailer\migrations\helpers\MailerSchemaHelper;
use BarrelStrength\Sprout\transactional\components\emailvariants\TransactionalEmailEmailVariant;
use BarrelStrength\Sprout\transactional\components\mailers\TransactionalMailer;
use Craft;
use craft\db\Migration;

class m211101_000000_run_install_migration extends Migration
{
    public const SPROUT_KEY = 'sprout';
    public const MODULES_KEY = self::SPROUT_KEY . '.sprout-module-core.modules';
    public const MODULE_ID = 'sprout-module-transactional';
    public const MODULE_CLASS = 'BarrelStrength\Sprout\transactional\TransactionalModule';
    public const DEFAULT_EMAIL_TYPE = 'BarrelStrength\Sprout\mailer\components\emailtypes';

    public function safeUp(): void
    {
        $coreModuleSettingsKey = self::MODULES_KEY . '.' . self::MODULE_CLASS;

        MailerSchemaHelper::createDefaultMailerIfNoTypeExists(
            TransactionalEmailEmailVariant::class,
            TransactionalMailer::class
        );
        MailerSchemaHelper::createEmailTypeIfNoTypeExists(
            EmailMessageEmailType::class, [
            'name' => 'Email Message',
        ]);

        // Check if plugin is installed:
        $sproutFormsIsInstalled = Craft::$app->getPlugins()->isPluginInstalled('sprout-forms');

        if ($sproutFormsIsInstalled) {
            MailerSchemaHelper::createEmailTypeIfNoTypeExists(
                FormSummaryEmailType::class, [
                'name' => 'Form Summary',
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
