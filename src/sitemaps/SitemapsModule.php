<?php

namespace BarrelStrength\Sprout\sitemaps;

use BarrelStrength\Sprout\core\db\MigrationTrait;
use BarrelStrength\Sprout\core\editions\EditionTrait;
use BarrelStrength\Sprout\core\helpers\RegexHelper;
use BarrelStrength\Sprout\core\modules\CpNavHelper;
use BarrelStrength\Sprout\core\modules\Settings;
use BarrelStrength\Sprout\core\modules\SettingsHelper;
use BarrelStrength\Sprout\core\modules\SproutModuleTrait;
use BarrelStrength\Sprout\core\modules\TranslatableTrait;
use BarrelStrength\Sprout\core\Sprout;
use BarrelStrength\Sprout\core\twig\SproutVariable;
use BarrelStrength\Sprout\sitemaps\sitemapmetadata\SitemapMetadata;
use BarrelStrength\Sprout\sitemaps\sitemaps\XmlSitemap;
use BarrelStrength\Sprout\uris\UrisModule;
use Craft;
use craft\config\BaseConfig;
use craft\events\RegisterCpNavItemsEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\services\UserPermissions;
use craft\web\UrlManager;
use craft\web\View;
use yii\base\Event;
use yii\base\Module;

/**
 * @property SitemapMetadata $sitemaps
 * @property XmlSitemap $xmlSitemap
 */
class SitemapsModule extends Module
{
    use SproutModuleTrait;
    use EditionTrait;
    use MigrationTrait;
    use TranslatableTrait;

    public static function getInstance(): SitemapsModule
    {
        UrisModule::getInstance();

        /** @var SitemapsModule $module */
        $module = Sprout::getSproutModule(static::class, 'sprout-module-sitemaps');

        return $module;
    }

    public static function getDisplayName(): string
    {
        $displayName = Craft::t('sprout-module-core', 'Sitemaps');

        return $displayName;
    }

    public static function getShortName(): string
    {
        return 'sitemaps';
    }

    public static function getDescription(): string
    {
        return Craft::t('sprout-module-core', 'Manage XML sitemaps');
    }

    public static function getUpgradeMessage(): string
    {
        return Craft::t('sprout-module-core', 'Upgrade to Sprout Sitemaps PRO to manage unlimited Content and Content Query Sitemaps.');
    }

    public function init(): void
    {
        parent::init();

        $this->registerTranslations();

        $this->setComponents([
            'sitemaps' => SitemapMetadata::class,
            'xmlSitemap' => XmlSitemap::class,
        ]);

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function(RegisterUrlRulesEvent $event): void {
                $event->rules = array_merge($event->rules, $this->getSiteUrlRules());
            });

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function(RegisterUrlRulesEvent $event): void {
                $event->rules = array_merge($event->rules, $this->getCpUrlRules());
            });

        Event::on(
            View::class,
            View::EVENT_REGISTER_CP_TEMPLATE_ROOTS,
            function(RegisterTemplateRootsEvent $e): void {
                $e->roots['sprout-module-sitemaps'] = $this->getBasePath() . DIRECTORY_SEPARATOR . 'templates';
            });

        Event::on(
            SproutVariable::class,
            SproutVariable::EVENT_INIT,
            function(Event $event): void {
                $event->sender->registerModule($this);
            });

        Event::on(
            Settings::class,
            Settings::INTERNAL_SPROUT_EVENT_REGISTER_CP_SETTINGS_NAV_ITEMS,
            function(RegisterCpNavItemsEvent $event): void {
                $groupName = Craft::t('sprout-module-sitemaps', 'SEO');
                $newNavItems = $this->getSproutCpSettingsNavItems();
                $event->navItems = CpNavHelper::mergeSproutCpSettingsNavItems(
                    $event->navItems,
                    $newNavItems,
                    $groupName
                );
            });

        Event::on(
            Settings::class,
            Settings::INTERNAL_SPROUT_EVENT_REGISTER_CRAFT_CP_SIDEBAR_NAV_ITEMS,
            function(RegisterCpNavItemsEvent $event): void {
                $event->navItems[] = $this->getCraftCpSidebarNavItems();
            });

        Event::on(
            Settings::class,
            Settings::INTERNAL_SPROUT_EVENT_REGISTER_CRAFT_CP_SETTINGS_NAV_ITEMS,
            function(RegisterCpNavItemsEvent $event): void {
                $event->navItems['sprout-module-sitemaps'] = $this->getCraftCpSettingsNavItems();
            });

        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            function(RegisterUserPermissionsEvent $event): void {
                $event->permissions[] = [
                    'heading' => Craft::t('sprout-module-sitemaps', 'Sprout Module | Sitemaps'),
                    'permissions' => $this->getUserPermissions(),
                ];
            });
    }

    public function createSettingsModel(): SitemapsSettings
    {
        return new SitemapsSettings();
    }

    public function getSettings(): SitemapsSettings|BaseConfig
    {
        return SettingsHelper::getSettingsConfig($this, SitemapsSettings::class);
    }

    protected function getCraftCpSidebarNavItems(): array
    {
        if (!Craft::$app->getUser()->checkPermission(self::p('accessModule'))) {
            return [];
        }

        return [
            'group' => Craft::t('sprout-module-sitemaps', 'Sitemaps'),
            'icon' => self::svg('icons/icon-mask.svg'),
            'navItems' => [
                'sitemaps' => [
                    'label' => Craft::t('sprout-module-sitemaps', 'Sitemaps'),
                    'url' => 'sprout/sitemaps',
                ],
            ],
        ];
    }

    protected function getCraftCpSettingsNavItems(): array
    {
        return [
            'label' => self::getDisplayName(),
            'url' => 'sprout/settings/sitemaps',
            'icon' => self::svg('icons/icon.svg'),
        ];
    }

    protected function getSproutCpSettingsNavItems(): array
    {
        return [
            'sitemaps' => [
                'label' => self::getDisplayName(),
                'url' => 'sprout/settings/sitemaps',
            ],
        ];
    }

    protected function getCpUrlRules(): array
    {
        return [
            'sprout/sitemaps/edit/<sourceKey:custom-query|custom-pages>/<sitemapMetadataUid:' . RegexHelper::UUID_PATTERN . '>' =>
                'sprout-module-sitemaps/sitemap-metadata/custom-sitemap-metadata-edit-template',
            'sprout/sitemaps/<sourceKey:custom-query|custom-pages>/new' =>
                'sprout-module-sitemaps/sitemap-metadata/custom-sitemap-metadata-edit-template',
            'sprout/sitemaps' =>
                'sprout-module-sitemaps/sitemap-metadata/sitemap-metadata-index-template',

            // Settings
            'sprout/settings/sitemaps' => [
                'template' => 'sprout-module-sitemaps/_settings/sitemaps',
            ],

            // Welcome
            'sprout/welcome/sitemaps' => [
                'template' => 'sprout-module-sitemaps/_admin/welcome',
            ],
            'sprout/upgrade/sitemaps' => [
                'template' => 'sprout-module-sitemaps/_admin/upgrade',
            ],
        ];
    }

    protected function getUserPermissions(): array
    {
        return [
            self::p('editSitemaps') => [
                'label' => Craft::t('sprout-module-sitemaps', 'Edit Sitemaps'),
            ],
        ];
    }

    /**
     * Match dynamic sitemap URLs
     *
     * Example matches include:
     *
     * Sitemap Index Page
     * - sitemap.xml
     *
     * Content Sitemaps and Custom Queries
     * - sitemap-[uid]-1.xml
     * - sitemap-[uid]-2.xml
     *
     * Special Groupings
     * - sitemap-singles.xml
     * - sitemap-custom-pages.xml
     */
    protected function getSiteUrlRules(): array
    {
        if (!self::isEnabled()) {
            return [];
        }

        return [
            'sitemap-<sitemapMetadataUid:' . RegexHelper::UUID_PATTERN . '>-<pageNumber:\d+>.xml' =>
                'sprout-module-sitemaps/xml-sitemap/render-xml-sitemap',
            'sitemap-<sitemapMetadataUid:singles|custom-pages>.xml' =>
                'sprout-module-sitemaps/xml-sitemap/render-xml-sitemap',
            'sitemap.xml' =>
                'sprout-module-sitemaps/xml-sitemap/render-xml-sitemap',
        ];
    }
}
