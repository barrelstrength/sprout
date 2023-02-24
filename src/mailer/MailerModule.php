<?php

namespace BarrelStrength\Sprout\mailer;

use BarrelStrength\Sprout\core\db\MigrationTrait;
use BarrelStrength\Sprout\core\modules\CpNavHelper;
use BarrelStrength\Sprout\core\modules\Settings;
use BarrelStrength\Sprout\core\modules\SettingsHelper;
use BarrelStrength\Sprout\core\modules\SproutModuleTrait;
use BarrelStrength\Sprout\core\modules\TranslatableTrait;
use BarrelStrength\Sprout\core\Sprout;
use BarrelStrength\Sprout\core\twig\SproutVariable;
use BarrelStrength\Sprout\datastudio\datasources\DataSources;
use BarrelStrength\Sprout\datastudio\DataStudioModule;
use BarrelStrength\Sprout\mailer\audience\Audiences;
use BarrelStrength\Sprout\mailer\components\datasources\SubscriberListDataSource;
use BarrelStrength\Sprout\mailer\components\elements\audience\AudienceElement;
use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\mailer\components\elements\subscriber\SubscriberElement;
use BarrelStrength\Sprout\mailer\email\EmailTypes;
use BarrelStrength\Sprout\mailer\emailthemes\EmailThemes;
use BarrelStrength\Sprout\mailer\mailers\Mailers;
use BarrelStrength\Sprout\mailer\subscribers\SubscriberLists;
use BarrelStrength\Sprout\mailer\subscribers\SubscriberListsVariable;
use Craft;
use craft\config\BaseConfig;
use craft\events\DefineFieldLayoutFieldsEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterCpNavItemsEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\models\FieldLayout;
use craft\services\Elements;
use craft\services\UserPermissions;
use craft\web\UrlManager;
use craft\web\View;
use Twig\Extra\CssInliner\CssInlinerExtension;
use yii\base\Event;
use yii\base\Module;

/**
 * @property Audiences $audiences
 * @property Mailers $mailers
 * @property EmailThemes $emailThemes
 * @property EmailTypes $emailTypes
 * @property SubscriberLists $subscriberLists
 */
class MailerModule extends Module
{
    use SproutModuleTrait;
    use MigrationTrait;
    use TranslatableTrait;

    public static function isEnabled(): bool
    {
        return true;
    }

    public static function getInstance(): MailerModule
    {
        DataStudioModule::getInstance();

        /** @var MailerModule $module */
        $module = Sprout::getSproutModule(static::class, 'sprout-module-mailer');

        return $module;
    }

    public static function getDisplayName(): string
    {
        $displayName = Craft::t('sprout-module-core', 'Mailer');

        return $displayName;
    }

    public static function getShortName(): string
    {
        return 'mailer';
    }

    public static function getDescription(): string
    {
        return Craft::t('sprout-module-core', 'Adds support for Audiences, subscribers, previews, and other shared email functionality');
    }

    public function init(): void
    {
        parent::init();

        $this->registerTranslations();

        $this->setComponents([
            'audiences' => Audiences::class,
            'mailers' => Mailers::class,
            'emailThemes' => EmailThemes::class,
            'emailTypes' => EmailTypes::class,
            'subscriberLists' => SubscriberLists::class,
        ]);

        Craft::$app->view->registerTwigExtension(new CssInlinerExtension());

        Event::on(
            SproutVariable::class,
            SproutVariable::EVENT_INIT,
            function(Event $event): void {
                $event->sender->registerModule($this);
                $event->sender->registerVariable('lists', new SubscriberListsVariable());
            });

        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            function(RegisterUserPermissionsEvent $event): void {
                $event->permissions[] = [
                    'heading' => 'Sprout Module | Mailer',
                    'permissions' => $this->getUserPermissions(),
                ];
            });

        Event::on(
            Elements::class,
            Elements::EVENT_REGISTER_ELEMENT_TYPES,
            static function(RegisterComponentTypesEvent $event): void {
                $event->types[] = EmailElement::class;
                $event->types[] = AudienceElement::class;
                $event->types[] = SubscriberElement::class;
            }
        );

        Event::on(
            FieldLayout::class,
            FieldLayout::EVENT_DEFINE_NATIVE_FIELDS,
            static function(DefineFieldLayoutFieldsEvent $event): void {
                if ($event->sender->type === AudienceElement::class) {
                    AudienceElement::defineNativeFields($event);
                }
            }
        );

        Event::on(
            DataSources::class,
            DataSources::INTERNAL_SPROUT_EVENT_REGISTER_DATA_SOURCES,
            static function(RegisterComponentTypesEvent $event): void {
                $event->types[] = SubscriberListDataSource::class;
            });

        Event::on(
            Settings::class,
            Settings::EVENT_REGISTER_SPROUT_CRAFT_CP_SIDEBAR_NAV_ITEMS,
            function(RegisterCpNavItemsEvent $event): void {
                $event->navItems[] = $this->getCraftCpSidebarNavItems();
            });

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function(RegisterUrlRulesEvent $event): void {
                $event->rules = array_merge($event->rules, $this->getCpUrlRules());
            });

        Event::on(
            Settings::class,
            Settings::EVENT_REGISTER_SPROUT_CP_SETTINGS_NAV_ITEMS,
            function(RegisterCpNavItemsEvent $event): void {
                $groupName = Craft::t('sprout-module-mailer', 'Email');
                $newNavItems = $this->getSproutCpSettingsNavItems();
                $event->navItems = CpNavHelper::mergeSproutCpSettingsNavItems(
                    $event->navItems,
                    $newNavItems,
                    $groupName
                );
            });

        Event::on(
            Settings::class,
            Settings::EVENT_REGISTER_SPROUT_CRAFT_CP_SETTINGS_NAV_ITEMS,
            function(RegisterCpNavItemsEvent $event): void {
                $event->navItems['sprout-module-mailer'] = $this->getCraftCpSettingsNavItems();
            });

        Event::on(
            View::class,
            View::EVENT_REGISTER_CP_TEMPLATE_ROOTS,
            function(RegisterTemplateRootsEvent $e): void {
                $e->roots['sprout-module-mailer'] = $this->getBasePath() . DIRECTORY_SEPARATOR . 'templates';
            });

        $this->registerProjectConfigEventListeners();
    }

    public function createSettingsModel(): MailerSettings
    {
        return new MailerSettings();
    }

    public function getSettings(): MailerSettings|BaseConfig
    {
        return SettingsHelper::getSettingsConfig($this, MailerSettings::class);
    }

    protected function getCraftCpSidebarNavItems(): array
    {
        if (!Craft::$app->getUser()->checkPermission(self::p('accessModule'))) {
            return [];
        }

        return [
            'group' => Craft::t('sprout-module-mailer', 'Email'),
            'icon' => self::svg('icons/icon-mask.svg'),
            'navItems' => [
                'audiences' => [
                    'label' => Craft::t('sprout-module-mailer', 'Audiences'),
                    'url' => 'sprout/email/audiences',
                ],
                'subscribers' => [
                    'label' => Craft::t('sprout-module-mailer', 'Subscribers'),
                    'url' => 'sprout/email/subscribers',
                ],
            ],
        ];
    }

    protected function getCraftCpSettingsNavItems(): array
    {
        return [
            'label' => Craft::t('sprout-module-mailer', 'Email'),
            'url' => 'sprout/settings/email-themes',
            'icon' => self::svg('icons/icon.svg'),
        ];
    }

    protected function getSproutCpSettingsNavItems(): array
    {
        return [
            'email-themes' => [
                'label' => Craft::t('sprout-module-mailer', 'Email Themes'),
                'url' => 'sprout/settings/email-themes',
            ],
            'mailers' => [
                'label' => Craft::t('sprout-module-mailer', 'Mailers'),
                'url' => 'sprout/settings/mailers',
            ],
        ];
    }

    protected function getCpUrlRules(): array
    {
        return [
            // Email
            'sprout/email' =>
                'sprout-module-core/settings/redirect-nav-item',

            // Audience
            'sprout/email/audiences/edit/<elementId:\d+>' =>
                'elements/edit',
            'sprout/email/audiences/<audienceTypeHandle:.*>/new' =>
                'sprout-module-mailer/audience/create-audience',
            'sprout/email/audiences' =>
                'sprout-module-mailer/audience/audience-index-template',

            // Subscribers
            'sprout/email/subscribers/new' =>
                'elements/edit',
            'sprout/email/subscribers/<userId:\d+>' =>
                'sprout-module-mailer/subscribers/edit-subscriber-template',
            'sprout/email/subscribers/<listHandle:.*>' => [
                'template' => 'sprout-module-mailer/subscribers/index',
            ],
            'sprout/email/subscribers' => [
                'template' => 'sprout-module-mailer/subscribers/index',
            ],

            // Settings: Email Themes
            'sprout/settings/email-themes/new' =>
                'sprout-module-mailer/email-themes/edit',
            'sprout/settings/email-themes/edit/<emailThemeId:\d+>' =>
                'sprout-module-mailer/email-themes/edit',
            'sprout/settings/email-themes' =>
                'sprout-module-mailer/email-themes/entry-types-index-template',

            // Settings: Mailers
            'sprout/settings/mailers/edit/new' =>
                'sprout-module-mailer/mailer/edit',
            'sprout/settings/mailers/edit/<mailerId:.*>' =>
                'sprout-module-mailer/mailer/edit',
            'sprout/settings/mailers' =>
                'sprout-module-mailer/mailer/mailers-index-template',

            // Preview
            'sprout/email/preview/<emailId:\d+>' =>
                'sprout-module-mailer/preview/preview',
        ];
    }

    protected function getUserPermissions(): array
    {
        return [
            self::p('editSubscribers') => [
                'label' => Craft::t('sprout-module-mailer', 'Edit Subscribers'),
            ],
        ];
    }

    private function registerProjectConfigEventListeners(): void
    {
        $projectConfigService = Craft::$app->getProjectConfig();

        // EMAIL THEMES
        $key = self::projectConfigPath('emailThemes.{uid}');

        $emailThemesService = $this->emailThemes;
        $projectConfigService->onAdd($key, [$emailThemesService, 'handleChangedFieldLayout'])
            ->onUpdate($key, [$emailThemesService, 'handleChangedFieldLayout'])
            ->onRemove($key, [$emailThemesService, 'handleDeletedFieldLayout']);

        // MAILERS

        $key = self::projectConfigPath('mailers.{uid}');

        $mailersService = $this->mailers;
        $projectConfigService->onAdd($key, [$mailersService, 'handleChangedMailer'])
            ->onUpdate($key, [$mailersService, 'handleChangedMailer'])
            ->onRemove($key, [$mailersService, 'handleDeletedMailer']);

        //        Event::on(ProjectConfig::class, ProjectConfig::EVENT_REBUILD, static function(RebuildConfigEvent $event) {
        //            $event->config['commerce'] = ProjectConfigData::rebuildProjectConfig();
        //        });
    }
}
