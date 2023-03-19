<?php

namespace BarrelStrength\Sprout\mailer\migrations\helpers;

use BarrelStrength\Sprout\mailer\components\emailthemes\EmailMessageTheme;
use BarrelStrength\Sprout\mailer\components\mailers\SystemMailer;
use BarrelStrength\Sprout\mailer\MailerModule;
use Craft;
use craft\helpers\Json;
use craft\helpers\StringHelper;

class MailerSchemaHelper
{
    public const SPROUT_KEY = 'sprout';
    public const DEFAULT_EMAIL_THEME = 'BarrelStrength\Sprout\mailer\components\emailthemes\EmailMessageTheme';

    public static function insertDefaultMailerSettings(): void
    {
        $mailerSettingsKey = self::SPROUT_KEY . '.' . MailerModule::getInstance()->id . '.mailers.' . StringHelper::UUID();

        Craft::$app->getProjectConfig()->set($mailerSettingsKey, [
            'name' => 'System Mailer',
            'type' => SystemMailer::class,
            'settings' => Json::encode([]),
        ]);
    }

    public static function createDefaultEmailThemeFieldLayout(): void
    {
        $emailTheme = new EmailMessageTheme();
        $fieldLayout = $emailTheme->getFieldLayout();

        $projectConfig = Craft::$app->getProjectConfig();

        $layoutUid = StringHelper::UUID();
        $configPath = MailerModule::projectConfigPath('emailThemes.' . $layoutUid);
        $projectConfig->set($configPath, $fieldLayout->getConfig());
    }
}
