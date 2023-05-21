<?php

namespace BarrelStrength\Sprout\core\modules;

use Craft;
use craft\config\BaseConfig;
use craft\helpers\App;
use craft\helpers\Typecast;
use Throwable;
use yii\base\Model;
use yii\base\Module;
use yii\db\Exception;

class SettingsHelper
{
    /**
     * Returns a settings model and all settings for a given config
     *
     * Settings priorities are as follows:
     * 1. Environment variables prefixed with: `SPROUT_` (optional)
     * 2. Settings found in file: `config/sprout-module-[name].php` (optional)
     * 3. Settings found in Project Config: `plugins.sprout.[configHandle]`
     * 4. Default settings from Settings model
     *
     * @example return new DataStudioSettingsModel();
     *
     * @inspiredBy `craft/services/Config::getConfigSettings()` and
     * ideally we can ditch this method and migrate to use a native
     * Craft method if/when they better support third-party modules
     */
    public static function getSettingsConfig(SproutModuleTrait|Module $module, string $settingsClass): BaseConfig
    {
        $configClass = $settingsClass;
        $envPrefix = $module::getEnvPrefix();

        $projectConfigService = Craft::$app->getProjectConfig();
        $config = $projectConfigService->get($module::projectConfigPath()) ?? [];

        $fileConfigSettings = Craft::$app->getConfig()->getConfigFromFile($module::getModuleId());

        $fileConfig = $fileConfigSettings instanceof BaseConfig
            ? $fileConfigSettings->toArray()
            : $fileConfigSettings;

        // Update Project Config values with File Config overrides
        $config = array_merge($config, $fileConfig);

        // Build a BaseConfig class
        $config = new $configClass($config);

        // Get any environment value overrides
        $envConfig = App::envConfig($configClass, $envPrefix);

        // Update current config with env overrides
        Typecast::properties($configClass, $envConfig);

        foreach ($envConfig as $name => $value) {
            // Use the fluent methods when possible, in case it has any value normalization logic
            if (method_exists($config, $name)) {
                try {
                    $config->$name($value);
                    continue;
                } catch (Throwable) {
                }
                $config->$name = $value;
            }
        }

        return $config;
    }

    /**
     * Validates and saves Sprout module settings to the Project Config
     */
    public static function saveSettings(string $key, Model $settings): bool
    {
        // Consider how validation handles settings that
        // may not be in the current post data
        if (!$settings->validate()) {
            return false;
        }

        $newSettings = $settings->toArray();

        Craft::$app->getProjectConfig()->set($key, $newSettings, "Update Sprout Settings for “{$key}”");

        return true;
    }

    /**
     * Save Sprout module settings to the shared sprout_settings table
     */
    public static function saveDbSettings($moduleId, $settings, $siteId): SettingsRecord|null
    {
        $settingsRecord = null;
        try {
            foreach ($settings as $name => $setting) {

                $settingsRecord = SettingsRecord::find()
                    ->select('*')
                    ->where([
                        'siteId' => $siteId,
                        'moduleId' => $moduleId,
                        'name' => $name,
                    ])
                    ->one();

                if (!$settingsRecord) {
                    $settingsRecord = new SettingsRecord();
                    $settingsRecord->siteId = $siteId;
                    $settingsRecord->moduleId = $moduleId;
                }

                $settingsRecord->name = $name;
                $settingsRecord->settings = $setting;

                if (!$settingsRecord->save()) {
                    throw new Exception('Unable to save Sprout settings.');
                }
            }
        } catch (Exception $exception) {
            throw new $exception;
        }

        return $settingsRecord;
    }
}
