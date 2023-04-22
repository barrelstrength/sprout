<?php

namespace BarrelStrength\Sprout\core;

use BarrelStrength\Sprout\core\db\MigrationTrait;
use BarrelStrength\Sprout\core\modules\CpNavHelper;
use BarrelStrength\Sprout\core\modules\Modules;
use BarrelStrength\Sprout\core\modules\Modules as ModulesService;
use BarrelStrength\Sprout\core\modules\Settings as SettingsService;
use BarrelStrength\Sprout\core\modules\SettingsHelper;
use BarrelStrength\Sprout\core\modules\SproutModuleTrait;
use BarrelStrength\Sprout\core\modules\TranslatableTrait;
use BarrelStrength\Sprout\core\twig\SproutExtension;
use BarrelStrength\Sprout\core\twig\SproutVariable;
use BarrelStrength\Sprout\core\twig\TemplateHelper;
use BarrelStrength\Sprout\core\web\assetbundles\vite\ViteAssetBundle;
use Craft;
use craft\config\BaseConfig;
use craft\console\Application as ConsoleApplication;
use craft\events\RegisterCpNavItemsEvent;
use craft\events\RegisterCpSettingsEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\helpers\App;
use craft\services\UserPermissions;
use craft\web\Application as WebApplication;
use craft\web\twig\variables\Cp;
use craft\web\UrlManager;
use craft\web\View;
use nystudio107\pluginvite\services\VitePluginService;
use yii\base\Event;
use yii\base\Module;

/**
 * @property SettingsService $coreSettings
 * @property ModulesService $coreModules
 * @property VitePluginService $vite
 */
class Sprout extends Module
{
    use SproutModuleTrait;
    use MigrationTrait;
    use TranslatableTrait;

    /**
     * Canonical list of plugin classes in the Sprout Framework
     *
     * @return string[]
     * @var array<string, class-string>
     */
    public const PLUGINS = [
        'sprout-data-studio' => 'BarrelStrength\SproutDataStudio\SproutDataStudio',
        'sprout-email' => 'BarrelStrength\SproutEmail\SproutEmail',
        'sprout-forms' => 'BarrelStrength\SproutForms\SproutForms',
        'sprout-redirects' => 'BarrelStrength\SproutRedirects\SproutRedirects',
        'sprout-sent-email' => 'BarrelStrength\SproutSentEmail\SproutSentEmail',
        'sprout-seo' => 'BarrelStrength\SproutSeo\SproutSeo',
        'sprout-sitemaps' => 'BarrelStrength\SproutSitemaps\SproutSitemaps',
    ];

    public static function getSproutModule(string $class, string $id): Module
    {
        if ($module = Craft::$app->getModule($id)) {
            return $module;
        }

        $module = new $class($id);
        static::setInstance($module);
        Craft::$app->setModule($id, $module);

        return $module::getInstance();
    }

    public static function getInstance(): Sprout
    {
        /** @var Sprout $module */
        $module = static::getSproutModule(static::class, 'sprout-module-core');

        return $module;
    }

    public static function getDisplayName(): string
    {
        return Craft::t('sprout-module-core', 'Control Panel');
    }

    public static function getShortName(): string
    {
        return 'core';
    }

    public function init(): void
    {
        parent::init();

        $this->registerTranslations();

        $this->setComponents([
            'coreSettings' => SettingsService::class,
            'coreModules' => ModulesService::class,

            // Register the vite service
            'vite' => [
                'class' => VitePluginService::class,
                'assetClass' => ViteAssetBundle::class,
                'useDevServer' => App::env('SPROUT_VITE_USE_DEV_SERVER'),
                'devServerPublic' => App::env('SPROUT_VITE_DEV_SERVER_PUBLIC'),
                'serverPublic' => App::env('SPROUT_VITE_SERVER_PUBLIC'),
                'errorEntry' => 'core/ErrorPage.js',
            ],
        ]);

        // The `@BarrelStrength/Sprout` syntax is needed for console commands. Resolves to /src
        Craft::setAlias('@BarrelStrength/Sprout', dirname(__DIR__));
        Craft::setAlias('@Sprout/Assets', dirname(__DIR__, 2) . '/assets');
        Craft::setAlias('@Sprout/TemplatePath', dirname(__DIR__, 2) . '/templates');
        Craft::setAlias('@Sprout/TemplateRoot', TemplateHelper::getSproutSiteTemplateRoot());

        if (Craft::$app instanceof ConsoleApplication) {
            $this->controllerNamespace = 'BarrelStrength\\Sprout\\core\\controllers\\console';
        }

        if (Craft::$app instanceof WebApplication) {
            $this->controllerNamespace = 'BarrelStrength\\Sprout\\core\\controllers\\web';
        }

        Craft::$app->view->registerTwigExtension(new SproutExtension());

        Event::on(
            SproutVariable::class,
            SproutVariable::EVENT_INIT,
            function(Event $event): void {
                $event->sender->registerModule($this);
            });

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function(RegisterUrlRulesEvent $event): void {
                $event->rules = array_merge($event->rules, $this->getCpUrlRules());
            });

        Event::on(
            Cp::class,
            Cp::EVENT_REGISTER_CP_NAV_ITEMS,
            static function(RegisterCpNavItemsEvent $event): void {
                $event->navItems = CpNavHelper::getUpdatedCpNavItems($event->navItems);
            });

        Event::on(
            Cp::class,
            Cp::EVENT_REGISTER_CP_SETTINGS,
            static function(RegisterCpSettingsEvent $event): void {
                $event->settings = CpNavHelper::getUpdatedCraftCpSettingsItems($event->settings);
            });

        Event::on(
            View::class,
            View::EVENT_REGISTER_CP_TEMPLATE_ROOTS,
            function(RegisterTemplateRootsEvent $e): void {
                $e->roots['sprout-module-core'] = $this->getBasePath() . DIRECTORY_SEPARATOR . 'templates';
            });

        Event::on(
            View::class,
            View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS,
            static function(RegisterTemplateRootsEvent $e): void {
                // get config hidden file character
                $root = TemplateHelper::getSproutSiteTemplateRoot();
                $e->roots[$root] = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'templates';
            });

        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            function(RegisterUserPermissionsEvent $event): void {
                $event->permissions[] = [
                    'heading' => Craft::t('sprout-module-core', 'Sprout | General'),
                    'permissions' => $this->getUserPermissions(),
                ];
            });
    }

    public function createSettingsModel(): SproutSettings
    {
        return new SproutSettings();
    }

    public function getSettings(): SproutSettings|BaseConfig
    {
        return SettingsHelper::getSettingsConfig($this, SproutSettings::class);
    }

    public function getUserPermissions(): array
    {
        $modules = self::getInstance()->coreModules->getAvailableModules();

        $accessPermissions = [];

        foreach ($modules as $module) {
            $accessPermissions[$module::p('accessModule')] = [
                'label' => Craft::t('sprout-module-core', 'Access {moduleName} Module', [
                    'moduleName' => $module::getDisplayName(),
                ]),
            ];
        }

        return $accessPermissions;
    }

    protected function getCpUrlRules(): array
    {
        return [
            'sprout/settings/control-panel' => [
                'template' => 'sprout-module-core/_settings',
            ],
            'sprout/settings/preview/<configFile:(.*)>' =>
                'sprout-module-core/settings/preview-config-settings-file',
        ];
    }
}
