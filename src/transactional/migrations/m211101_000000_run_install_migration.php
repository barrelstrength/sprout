<?php

namespace BarrelStrength\Sprout\transactional\migrations;

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
        $moduleSettingsKey = self::SPROUT_KEY . '.' . self::MODULE_ID;
        $coreModuleSettingsKey = self::MODULES_KEY . '.' . self::MODULE_CLASS;

        // @todo - fix default settings to import
        Craft::$app->getProjectConfig()->set($moduleSettingsKey, [
            'emailTemplateId' => self::DEFAULT_EMAIL_THEME,
            'enablePerEmailEmailTemplateIdOverride' => false,
        ], "Update Sprout CP Settings for “{$moduleSettingsKey}”");

        Craft::$app->getProjectConfig()->set($coreModuleSettingsKey, [
            'alternateName' => '',
            'enabled' => true,
        ]);
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
