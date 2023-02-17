<?php

namespace BarrelStrength\Sprout\core\modules;

use BarrelStrength\Sprout\core\Sprout;
use Craft;
use craft\config\BaseConfig;
use craft\events\RegisterCpNavItemsEvent;
use craft\helpers\App;
use craft\helpers\Typecast;
use craft\helpers\UrlHelper;
use Exception;
use Throwable;
use yii\base\Component;
use yii\base\Model;
use yii\base\Module;

class Settings extends Component
{
    public const EVENT_REGISTER_SPROUT_CRAFT_CP_SETTINGS_NAV_ITEMS = 'registerSproutCraftCpSettingsNavItems';

    public const EVENT_REGISTER_SPROUT_CRAFT_CP_SIDEBAR_NAV_ITEMS = 'registerSproutCraftCpSidebarNavItems';

    public const EVENT_REGISTER_SPROUT_CP_SETTINGS_NAV_ITEMS = 'registerSproutCpSettingsNavItems';

    /**
     * Returns items that will be added to Craft CP Settings navigation
     */
    private array $_craftCpSettingsNavItems = [];

    /**
     * Returns items that will be added to Craft CP sidebar navigation
     */
    private array $_craftCpSidebarNavItems = [];

    /**
     * Returns items that will be added to Sprout Settings sidebar navigation
     */
    private array $_sproutCpSettingsNavItems = [];

    /**
     * Each Sprout module manages any routes used for Craft CP sidebar navigation
     *
     * $event->navItems = [
     *   [
     *     'group' => 'Data Studio',
     *     'icon' => '/var/www/html/dev-sprout/sprout/assets/dist/static/data-studio/icons/icon-mask.svg',
     *     'navItems' => [
     *       'data-sets' => [
     *         'label' => "Data Sets",
     *         'url' => "sprout/data-studio"
     *       ]
     *     ]
     *   ],
     * ];
     */
    public function initCraftCpSidebarNavItems(): void
    {
        $event = new RegisterCpNavItemsEvent([
            'navItems' => [],
        ]);

        $this->trigger(self::EVENT_REGISTER_SPROUT_CRAFT_CP_SIDEBAR_NAV_ITEMS, $event);

        $this->_craftCpSidebarNavItems = $event->navItems;
    }

    public function getCraftCpSidebarNavItems(): array
    {
        if (empty($this->_craftCpSidebarNavItems)) {
            $this->initCraftCpSidebarNavItems();
        }

        return $this->_craftCpSidebarNavItems;
    }

    /**
     * Each Sprout module manages any routes used for Craft CP Settings page navigation
     *
     * $event->navItems = [
     *   [
     *     'group' => 'Control Panel',
     *     'url' => 'https://www.craftcms.com/admin/sprout/settings/control-panel?site=en_us',
     *     'icon' => '/var/www/html/dev-sprout/sprout/assets/dist/static/core/icons/icon.svg',
     *   ]
     * ];
     */
    public function initCraftCpSettingsNavItems(): void
    {
        $navItems = [];
        $navItems['sprout-module-core'] = [
            'label' => Sprout::getDisplayName(),
            'url' => UrlHelper::cpUrl('sprout/settings/control-panel'),
            'icon' => Craft::getAlias('@Sprout/Assets/dist/static/core/icons/icon.svg'),
        ];

        $event = new RegisterCpNavItemsEvent([
            'navItems' => $navItems,
        ]);

        $this->trigger(self::EVENT_REGISTER_SPROUT_CRAFT_CP_SETTINGS_NAV_ITEMS, $event);

        $this->_craftCpSettingsNavItems = $event->navItems;
    }

    public function getCraftCpSettingsNavItems(): array
    {
        if (empty($this->_craftCpSettingsNavItems)) {
            $this->initCraftCpSettingsNavItems();
        }

        return $this->_craftCpSettingsNavItems;
    }

    /**
     * Each Module will manage any routes that map the URL to additional behavior
     *
     * $event->navItems['Group Name'] = [
     *   'moduleIdShortName' => [
     *     'label' => 'Reports',
     *     'url' => 'sprout/settings/reports',
     *   ],
     * ];
     */
    public function initSproutCpSettingsNavItems(): void
    {
        $globalNavItem = [];
        $event = new RegisterCpNavItemsEvent([
            'navItems' => [],
        ]);

        $this->trigger(self::EVENT_REGISTER_SPROUT_CP_SETTINGS_NAV_ITEMS, $event);

        $globalHeading = Craft::t('sprout-module-core', 'Global Settings');

        $globalNavItem[$globalHeading] = [
            'control-panel' => [
                'label' => Sprout::getDisplayName(),
                'url' => 'sprout/settings/control-panel',
            ],
        ];

        ksort($event->navItems, SORT_NATURAL);

        $navItems = array_merge($globalNavItem, $event->navItems);

        $sortedNavItems = [];
        foreach ($navItems as $groupName => $navItem) {
            ksort($navItem, SORT_NATURAL);
            $sortedNavItems[$groupName] = $navItem;
        }

        $this->_sproutCpSettingsNavItems = $sortedNavItems;
    }

    public function getSproutCpSettingsNavItems(): array
    {
        if (empty($this->_sproutCpSettingsNavItems)) {
            $this->initSproutCpSettingsNavItems();
        }

        return $this->_sproutCpSettingsNavItems;
    }

    /**
     * Returns an alternate name for a given module
     *
     * Alternate names will only update in key navigation areas. They may not update
     * in all areas – some modules are grouped by a group name that will not change.
     * Module names will not be updated in URLs, examples, or the Settings area.
     */
    public function getAlternateName(string $class): string
    {
        $key = Sprout::projectConfigPath('modules.' . $class . '.alternateName');

        if (!$alternateName = Craft::$app->projectConfig->get($key)) {
            return '';
        }

        return $alternateName;
    }

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
            : $fileConfigSettings ?? [];

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
    public function saveSettings(string $key, Model $settings): bool
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
    public function saveDbSettings($moduleId, $settings, $siteId): SettingsRecord|null
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
                    throw new \yii\db\Exception('Unable to save Sprout settings.');
                }
            }
        } catch (Exception $exception) {
            throw new $exception;
        }

        return $settingsRecord;
    }
}
