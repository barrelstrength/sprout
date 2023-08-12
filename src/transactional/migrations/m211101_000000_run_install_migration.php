<?php

namespace BarrelStrength\Sprout\transactional\migrations;

use BarrelStrength\Sprout\mailer\migrations\helpers\MailerSchemaHelper;
use BarrelStrength\Sprout\transactional\components\emailtypes\TransactionalEmailEmailType;
use Craft;
use craft\db\Migration;

class m211101_000000_run_install_migration extends Migration
{
    public const SPROUT_KEY = 'sprout';
    public const MODULES_KEY = self::SPROUT_KEY . '.sprout-module-core.modules';
    public const MODULE_ID = 'sprout-module-transactional';
    public const MODULE_CLASS = 'BarrelStrength\Sprout\transactional\TransactionalModule';
    public const DEFAULT_EMAIL_THEME = 'BarrelStrength\Sprout\mailer\components\emailthemes';

    public function safeUp(): void
    {
        $coreModuleSettingsKey = self::MODULES_KEY . '.' . self::MODULE_CLASS;

        MailerSchemaHelper::createDefaultMailer(TransactionalEmailEmailType::class);
        MailerSchemaHelper::createDefaultEmailTheme();

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
