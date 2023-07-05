<?php

namespace BarrelStrength\Sprout\forms\formtemplates;

use BarrelStrength\Sprout\forms\FormsModule;
use Craft;
use craft\helpers\ProjectConfig;
use craft\helpers\StringHelper;

class FormThemeHelper
{
    public static function getFormThemes(): array
    {
        $settings = FormsModule::getInstance()->getSettings();

        $themeConfigs = ProjectConfig::unpackAssociativeArray($settings->formThemes);

        foreach ($themeConfigs as $uid => $config) {
            $themes[$uid] = self::getFormThemeModel($config, $uid);
        }

        return $themes ?? [];
    }

    public static function saveFormThemes(array $themes): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $configPath = FormsModule::projectConfigPath('formThemes');
        $themeConfigs = [];

        foreach ($themes as $uid => $theme) {
            $themeConfigs[$uid] = $theme->getConfig();
        }

        if (!$projectConfig->set($configPath, ProjectConfig::packAssociativeArray($themeConfigs))) {
            return false;
        }

        return true;
    }

    public static function removeFormTheme(string $uid): bool
    {
        $themes = self::getFormThemes();

        unset($themes[$uid]);

        if (!self::saveFormThemes($themes)) {
            return false;
        }

        return true;
    }

    public static function getFormThemeByUid(string $uid): ?FormTemplateSet
    {
        $themes = self::getFormThemes();

        return $themes[$uid] ?? null;
    }

    public static function getFormThemeByHandle(string $handle = null): ?FormTemplateSet
    {
        $themes = FormsModule::getInstance()->formTemplates->getFormTemplateTypesInstances();

        return $themes[$handle] ?? null;
    }

    public static function getFormThemeModel(array $formThemeSettings, string $uid = null): ?FormTemplateSet
    {
        //$fieldLayout = FieldLayout::createFromConfig(reset($formThemeSettings['fieldLayouts']));

        $type = $formThemeSettings['type'];

        $emailTheme = new $type([
            'name' => $formThemeSettings['name'],
            //'htmlEmailTemplate' => $formThemeSettings['htmlEmailTemplate'] ?? null,
            //'textEmailTemplate' => $formThemeSettings['textEmailTemplate'] ?? null,
            //'copyPasteEmailTemplate' => $formThemeSettings['copyPasteEmailTemplate'] ?? null,
            //'fieldLayout' => $fieldLayout,
            'uid' => $uid ?? StringHelper::UUID(),
        ]);

        return $emailTheme;
    }

    public static function reorderFormThemes(array $uids = []): bool
    {
        $oldThemes = self::getFormThemes();
        $newThemes = [];

        foreach ($uids as $uid) {
            $newThemes[$uid] = $oldThemes[$uid];
        }

        if (!self::saveFormThemes($newThemes)) {
            return false;
        }

        return true;
    }

    public static function getDefaultFormTheme()
    {
        $themes = self::getFormThemes();

        return reset($themes) ?? null;
    }
}
