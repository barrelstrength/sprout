<?php

namespace BarrelStrength\Sprout\mailer\emailthemes;

use BarrelStrength\Sprout\mailer\MailerModule;
use Craft;
use craft\helpers\ProjectConfig;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;

class EmailThemeHelper
{
    public static function getEmailThemes(): array
    {
        $settings = MailerModule::getInstance()->getSettings();

        $themeConfigs = ProjectConfig::unpackAssociativeArray($settings->emailThemes);

        foreach ($themeConfigs as $uid => $config) {
            $themes[$uid] = self::getEmailThemeModel($config, $uid);
        }

        return $themes ?? [];
    }

    public static function saveEmailThemes(array $themes): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $configPath = MailerModule::projectConfigPath('emailThemes');
        $themeConfigs = [];

        foreach ($themes as $uid => $theme) {
            $themeConfigs[$uid] = $theme->getConfig();
        }

        if (!$projectConfig->set($configPath, ProjectConfig::packAssociativeArray($themeConfigs))) {
            return false;
        }

        return true;
    }

    public static function removeEmailTheme(string $uid): bool
    {
        $themes = self::getEmailThemes();

        unset($themes[$uid]);

        if (!self::saveEmailThemes($themes)) {
            return false;
        }

        return true;
    }

    public static function getEmailThemeByUid(string $uid): ?EmailTheme
    {
        $themes = self::getEmailThemes();

        return $themes[$uid] ?? null;
    }

    public static function getEmailThemeByHandle(string $handle = null): ?EmailTheme
    {
        $emailThemes = MailerModule::getInstance()->emailThemes->getEmailThemeTypeInstances();

        return $emailThemes[$handle] ?? null;
    }

    public static function getEmailThemeModel(array $emailThemeSettings, string $uid = null): ?EmailTheme
    {
        $fieldLayout = FieldLayout::createFromConfig(reset($emailThemeSettings['fieldLayouts']));

        $type = $emailThemeSettings['type'];

        $emailTheme = new $type([
            'name' => $emailThemeSettings['name'],
            'htmlEmailTemplate' => $emailThemeSettings['htmlEmailTemplate'] ?? null,
            'textEmailTemplate' => $emailThemeSettings['textEmailTemplate'] ?? null,
            'copyPasteEmailTemplate' => $emailThemeSettings['copyPasteEmailTemplate'] ?? null,
            'fieldLayout' => $fieldLayout,
            'uid' => $uid ?? StringHelper::UUID(),
        ]);

        return $emailTheme;
    }

    public static function reorderEmailThemes(array $uids = []): bool
    {
        $oldThemes = self::getEmailThemes();
        $newThemes = [];

        foreach ($uids as $uid) {
            $newThemes[$uid] = $oldThemes[$uid];
        }

        if (!self::saveEmailThemes($newThemes)) {
            return false;
        }

        return true;
    }

    public static function getDefaultEmailTheme()
    {
        $themes = self::getEmailThemes();

        return reset($themes) ?? null;
    }
}
