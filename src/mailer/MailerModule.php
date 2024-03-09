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
use BarrelStrength\Sprout\mailer\components\elements\subscriber\SubscriberHelper;
use BarrelStrength\Sprout\mailer\emailtypes\EmailTypeHelper;
use BarrelStrength\Sprout\mailer\emailtypes\EmailTypes;
use BarrelStrength\Sprout\mailer\mailers\MailerHelper;
use BarrelStrength\Sprout\mailer\mailers\Mailers;
use BarrelStrength\Sprout\mailer\subscriberlists\SubscriberLists;
use BarrelStrength\Sprout\mailer\twig\MailerVariable;
use BarrelStrength\Sprout\sentemail\SentEmailModule;
use BarrelStrength\Sprout\transactional\TransactionalModule;
use Craft;
use craft\config\BaseConfig;
use craft\elements\db\UserQuery;
use craft\elements\User;
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
            'emailTypes' => EmailTypes::class,
            'subscriberLists' => SubscriberLists::class,
        ]);

        Craft::$app->view->registerTwigExtension(new CssInlinerExtension());

        Event::on(
            SproutVariable::class,
            SproutVariable::EVENT_INIT,
            function(Event $event): void {
                $event->sender->registerModule($this);
                $event->sender->registerVariable('mailer', new MailerVariable());
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
            }
        );

        Event::on(
            FieldLayout::class,
            FieldLayout::EVENT_DEFINE_NATIVE_FIELDS,
            [AudienceElement::class, 'defineNativeFields']);

        Event::on(
            FieldLayout::class,
            FieldLayout::EVENT_DEFINE_NATIVE_FIELDS,
            [SubscriberHelper::class, 'defineNativeSubscriberField']);

        Event::on(
            FieldLayout::class,
            FieldLayout::EVENT_DEFINE_NATIVE_FIELDS,
            [EmailTypeHelper::class, 'defineNativeFieldsPerEmailType']);

        Event::on(
            DataSources::class,
            DataSources::INTERNAL_SPROUT_EVENT_REGISTER_DATA_SOURCES,
            static function(RegisterComponentTypesEvent $event): void {
                $event->types[] = SubscriberListDataSource::class;
            });

        Event::on(
            Settings::class,
            Settings::INTERNAL_SPROUT_EVENT_REGISTER_CRAFT_CP_SIDEBAR_NAV_ITEMS,
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
            Settings::INTERNAL_SPROUT_EVENT_REGISTER_CP_SETTINGS_NAV_ITEMS,
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
            Settings::INTERNAL_SPROUT_EVENT_REGISTER_CRAFT_CP_SETTINGS_NAV_ITEMS,
            function(RegisterCpNavItemsEvent $event): void {
                $event->navItems['sprout-module-mailer'] = $this->getCraftCpSettingsNavItems();
            });

        Event::on(
            View::class,
            View::EVENT_REGISTER_CP_TEMPLATE_ROOTS,
            function(RegisterTemplateRootsEvent $e): void {
                $e->roots['sprout-module-mailer'] = $this->getBasePath() . DIRECTORY_SEPARATOR . 'templates';
            });

        Event::on(
            User::class,
            User::EVENT_REGISTER_SOURCES,
            [SubscriberHelper::class, 'defineAdditionalSources']
        );

        Event::on(
            User::class,
            User::EVENT_DEFINE_BEHAVIORS,
            [SubscriberHelper::class, 'attachSubscriberElementBehavior']
        );

        Event::on(
            UserQuery::class,
            UserQuery::EVENT_DEFINE_BEHAVIORS,
            [SubscriberHelper::class, 'attachSubscriberQueryBehavior']
        );

        Event::on(
            FieldLayout::class,
            FieldLayout::EVENT_DEFINE_NATIVE_FIELDS,
            [MailerHelper::class, 'defineNativeFields']
        );

        Event::on(
            FieldLayout::class,
            FieldLayout::EVENT_DEFINE_UI_ELEMENTS,
            [MailerHelper::class, 'defineNativeElements']
        );
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
        $navItems = [];

        $userService = Craft::$app->getUser();

        $settings = $this->getSettings();

        if (TransactionalModule::isEnabled() &&
            $userService->checkPermission(TransactionalModule::p('accessModule'))
        ) {
            $navItems['transactional-email'] = [
                'label' => Craft::t('sprout-module-mailer', 'Transactional'),
                'url' => 'sprout/email/transactional-email',
            ];
        }

        if (SentEmailModule::isEnabled() &&
            $userService->checkPermission(SentEmailModule::p('accessModule'))
        ) {
            $navItems['sent-email'] = [
                'label' => Craft::t('sprout-module-mailer', 'Sent Email'),
                'url' => 'sprout/email/sent-email',
            ];
        }

        // Make sure at least one Audience Types exists
        if (TransactionalModule::isEnabled() && $settings->enableAudiences && $this->audiences->getAudienceTypes()) {
            $navItems['audiences'] = [
                'label' => Craft::t('sprout-module-mailer', 'Audiences'),
                'url' => 'sprout/email/audiences',
            ];
        }

        return [
            'group' => Craft::t('sprout-module-mailer', 'Email'),
            'icon' => self::svg('icons/icon-mask.svg'),
            'url' => 'sprout/email',
            'navItems' => $navItems,
        ];
    }

    protected function getCraftCpSettingsNavItems(): array
    {
        return [
            'label' => Craft::t('sprout-module-mailer', 'Email'),
            'url' => 'sprout/settings/email-types',
            'icon' => self::svg('icons/icon.svg'),
        ];
    }

    protected function getSproutCpSettingsNavItems(): array
    {
        return [
            'audiences' => [
                'label' => Craft::t('sprout-module-mailer', 'Audiences'),
                'url' => 'sprout/settings/audiences',
            ],
            'mailers' => [
                'label' => Craft::t('sprout-module-mailer', 'Mailer Settings'),
                'url' => 'sprout/settings/mailers',
            ],
            'email-types' => [
                'label' => Craft::t('sprout-module-mailer', 'Email Types'),
                'url' => 'sprout/settings/email-types',
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
            'sprout/email/audiences/new' =>
                'sprout-module-mailer/audience/create-audience',
            'sprout/email/audiences' =>
                'sprout-module-mailer/audience/audience-index-template',

            // Settings: Email Types
            'sprout/settings/email-types/new' =>
                'sprout-module-mailer/email-types/edit',
            'sprout/settings/email-types/edit/<emailTypeUid:.*>' =>
                'sprout-module-mailer/email-types/edit',
            'sprout/settings/email-types' =>
                'sprout-module-mailer/email-types/email-types-index-template',

            // Settings: Mailers
            'sprout/settings/mailers/new' =>
                'sprout-module-mailer/mailer/edit',
            'sprout/settings/mailers/edit/<mailerUid:.*>' =>
                'sprout-module-mailer/mailer/edit',
            'sprout/settings/mailers' =>
                'sprout-module-mailer/mailer/mailers-index-template',

            // Settings
            'sprout/settings/audiences' => [
                'template' => 'sprout-module-mailer/_settings/audiences',
            ],

            // Preview
            'sprout/email/preview/<emailId:\d+>' =>
                'sprout-module-mailer/preview/preview',
        ];
    }

    protected function getUserPermissions(): array
    {
        return [
            self::p('editAudiences') => [
                'label' => Craft::t('sprout-module-mailer', 'Edit Audiences'),
            ],
            self::p('editSubscribers') => [
                'label' => Craft::t('sprout-module-mailer', 'Edit Subscribers'),
            ],
        ];
    }
}
