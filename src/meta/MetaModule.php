<?php

namespace BarrelStrength\Sprout\meta;

use BarrelStrength\Sprout\core\db\MigrationTrait;
use BarrelStrength\Sprout\core\editions\EditionTrait;
use BarrelStrength\Sprout\core\modules\CpNavHelper;
use BarrelStrength\Sprout\core\modules\Settings;
use BarrelStrength\Sprout\core\modules\SettingsHelper;
use BarrelStrength\Sprout\core\modules\SproutModuleTrait;
use BarrelStrength\Sprout\core\modules\TranslatableTrait;
use BarrelStrength\Sprout\core\Sprout;
use BarrelStrength\Sprout\core\twig\SproutVariable;
use BarrelStrength\Sprout\meta\components\fields\ElementMetadataField;
use BarrelStrength\Sprout\meta\globals\GlobalMetadata;
use BarrelStrength\Sprout\meta\metadata\ElementMetadata;
use BarrelStrength\Sprout\meta\metadata\MetadataVariable;
use BarrelStrength\Sprout\meta\metadata\OptimizeMetadata;
use BarrelStrength\Sprout\meta\schema\SchemaMetadata;
use BarrelStrength\Sprout\uris\UrisModule;
use Craft;
use craft\config\BaseConfig;
use craft\elements\Address;
use craft\events\AuthorizationCheckEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterCpNavItemsEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\events\SiteEvent;
use craft\services\Fields;
use craft\services\Sites;
use craft\services\UserPermissions;
use craft\web\UrlManager;
use craft\web\View;
use yii\base\Event;
use yii\base\Module;

/**
 * @property OptimizeMetadata $optimizeMetadata
 * @property GlobalMetadata $globalMetadata
 * @property ElementMetadata $elementMetadata
 * @property SchemaMetadata $schemaMetadata
 */
class MetaModule extends Module
{
    use SproutModuleTrait;
    use EditionTrait;
    use MigrationTrait;
    use TranslatableTrait;

    public static function getInstance(): MetaModule
    {
        UrisModule::getInstance();

        /** @var MetaModule $module */
        $module = Sprout::getSproutModule(static::class, 'sprout-module-meta');

        return $module;
    }

    public static function getDisplayName(): string
    {
        $displayName = Craft::t('sprout-module-core', 'Meta');

        return $displayName;
    }

    public static function getShortName(): string
    {
        return 'meta';
    }

    public static function getDescription(): string
    {
        return Craft::t('sprout-module-core', 'Manage metadata metadata');
    }

    public static function getUpgradeMessage(): string
    {
        return Craft::t('sprout-module-core', 'Upgrade to Sprout Meta PRO to enable multiple Metadata field mappings, manage redirects, and generate sitemaps.');
    }

    public function init(): void
    {
        parent::init();

        $this->registerTranslations();

        $this->setComponents([
            'optimizeMetadata' => OptimizeMetadata::class,
            'globalMetadata' => GlobalMetadata::class,
            'elementMetadata' => ElementMetadata::class,
            'schemaMetadata' => SchemaMetadata::class,
        ]);

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
                $e->roots['sprout-module-meta'] = $this->getBasePath() . DIRECTORY_SEPARATOR . 'templates';
            });

        Event::on(
            Settings::class,
            Settings::INTERNAL_SPROUT_EVENT_REGISTER_CP_SETTINGS_NAV_ITEMS,
            function(RegisterCpNavItemsEvent $event): void {
                $groupName = Craft::t('sprout-module-redirects', 'SEO');
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
                $event->navItems['sprout-module-meta'] = $this->getCraftCpSettingsNavItems();
            });

        Event::on(
            SproutVariable::class,
            SproutVariable::EVENT_INIT,
            function(Event $event): void {
                $event->sender->registerModule($this);
                $event->sender->registerVariable('meta', new MetadataVariable());
            });

        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            function(RegisterUserPermissionsEvent $event): void {
                $event->permissions[] = [
                    'heading' => 'Sprout Module | Meta',
                    'permissions' => $this->getUserPermissions(),
                ];
            });

        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            static function(RegisterComponentTypesEvent $event): void {
                $event->types[] = ElementMetadataField::class;
            });

        Event::on(
            Sites::class,
            Sites::EVENT_AFTER_SAVE_SITE,
            static function(SiteEvent $event): void {
                MetaModule::getInstance()->globalMetadata->handleDefaultSiteMetadata($event);
            });

        if (Craft::$app->getRequest()->getIsCpRequest()) {
            $checkAuth = static function(AuthorizationCheckEvent $event) {
                /** @var Address $address */
                $address = $event->sender;
                $canonicalId = $address->getCanonicalId();

                $globals = MetaModule::getInstance()->globalMetadata->getGlobalMetadata();

                if (
                    $canonicalId &&
                    $canonicalId === $globals->addressModel->id &&
                    $event->user->can(MetaModule::p('editGlobals'))
                ) {
                    $event->authorized = true;
                    $event->handled = true;
                }
            };

            Event::on(Address::class, Address::EVENT_AUTHORIZE_VIEW, $checkAuth);
            Event::on(Address::class, Address::EVENT_AUTHORIZE_SAVE, $checkAuth);
        }
    }

    public function createSettingsModel(): MetaSettings
    {
        return new MetaSettings();
    }

    public function getSettings(): MetaSettings
    {
        /** @var MetaSettings $settings */
        $settings = SettingsHelper::getSettingsConfig($this, MetaSettings::class);

        return $settings;
    }

    protected function getCraftCpSidebarNavItems(): array
    {
        if (!Craft::$app->getUser()->checkPermission(self::p('accessModule'))) {
            return [];
        }

        return [
            'group' => Craft::t('sprout-module-meta', 'SEO'),
            'icon' => self::svg('icons/icon-mask.svg'),
            'navItems' => [
                'meta' => [
                    'label' => Craft::t('sprout-module-meta', 'Meta'),
                    'url' => 'sprout/meta/globals',
                ],
            ],
        ];
    }

    protected function getCraftCpSettingsNavItems(): array
    {
        return [
            'label' => self::getDisplayName(),
            'url' => 'sprout/settings/meta',
            'icon' => self::svg('icons/icon.svg'),
        ];
    }

    protected function getSproutCpSettingsNavItems(): array
    {
        return [
            'meta' => [
                'label' => self::getDisplayName(),
                'url' => 'sprout/settings/meta',
            ],
        ];
    }

    protected function getUserPermissions(): array
    {
        return [
            self::p('editGlobals') => [
                'label' => Craft::t('sprout-module-meta', 'Edit Globals'),
            ],
        ];
    }

    protected function getCpUrlRules(): array
    {
        return [
            'sprout/meta/globals/<selectedTabHandle:[^\/]+>' =>
                'sprout-module-meta/global-metadata/edit-global-metadata',
            'sprout/meta/globals' =>
                'sprout-module-meta/global-metadata/hello',
            'sprout/meta' =>
                'sprout-module-meta/global-metadata/hello',

            // Settings
            'sprout/settings/meta' => [
                'template' => 'sprout-module-meta/_settings/metadata',
            ],

            // Welcome
            'sprout/welcome/meta' => [
                'template' => 'sprout-module-meta/_admin/welcome',
            ],
            'sprout/upgrade/meta' => [
                'template' => 'sprout-module-meta/_admin/upgrade',
            ],
        ];
    }

    //    public function getTwigVariables(): array
    //    {
    //        return [
    //            'meta' => MetaVariable::class,
    //        ];
    //    }
    //
    //    protected function getFieldTypes(): array
    //    {
    //        return [
    //            ElementMetadataField::class,
    //        ];
    //    }
}
