<?php

namespace BarrelStrength\Sprout\sentemail;

use BarrelStrength\Sprout\core\db\MigrationInterface;
use BarrelStrength\Sprout\core\db\MigrationTrait;
use BarrelStrength\Sprout\core\editions\EditionTrait;
use BarrelStrength\Sprout\core\modules\CpNavHelper;
use BarrelStrength\Sprout\core\modules\Settings;
use BarrelStrength\Sprout\core\modules\SettingsHelper;
use BarrelStrength\Sprout\core\modules\SproutModuleInterface;
use BarrelStrength\Sprout\core\modules\SproutModuleTrait;
use BarrelStrength\Sprout\core\modules\TranslatableTrait;
use BarrelStrength\Sprout\core\Sprout;
use BarrelStrength\Sprout\core\twig\SproutVariable;
use BarrelStrength\Sprout\mailer\MailerModule;
use BarrelStrength\Sprout\sentemail\components\elements\SentEmailElement;
use BarrelStrength\Sprout\sentemail\sentemail\SentEmails;
use Craft;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterCpNavItemsEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\services\Elements;
use craft\services\UserPermissions;
use craft\web\UrlManager;
use craft\web\View;
use yii\base\Event;
use yii\base\Module;
use yii\mail\BaseMailer;

/**
 * @property SentEmails $sentEmails
 */
class SentEmailModule extends Module implements SproutModuleInterface, MigrationInterface
{
    use SproutModuleTrait;
    use EditionTrait;
    use MigrationTrait;
    use TranslatableTrait;

    public static function getInstance(): SentEmailModule
    {
        MailerModule::getInstance();

        /** @var SentEmailModule $module */
        $module = Sprout::getSproutModule(static::class, 'sprout-module-sent-email');

        return $module;
    }

    public static function getDisplayName(): string
    {
        $displayName = Craft::t('sprout-module-core', 'Sent Email');

        return $displayName;
    }

    public static function getShortName(): string
    {
        return 'sent-email';
    }

    public static function getDescription(): string
    {
        return Craft::t('sprout-module-core', 'Track sent emails and resend messages');
    }

    public static function getUpgradeMessage(): string
    {
        return Craft::t('sprout-module-core', 'Upgrade to Sprout Sent Email PRO to manage Resend Emails.');
    }

    public function init(): void
    {
        parent::init();

        $this->registerTranslations();

        $this->setComponents([
            'sentEmails' => SentEmails::class,
        ]);

        Craft::setAlias('@BarrelStrength/Sprout/sentemail', __DIR__);

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
                $e->roots['sprout-module-sent-email'] = $this->getBasePath() . DIRECTORY_SEPARATOR . 'templates';
            });

        Event::on(
            Settings::class,
            Settings::INTERNAL_SPROUT_EVENT_REGISTER_CP_SETTINGS_NAV_ITEMS,
            function(RegisterCpNavItemsEvent $event): void {
                $groupName = Craft::t('sprout-module-sent-email', 'Email');
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
                $event->navItems['sprout-module-sent-email'] = $this->getCraftCpSettingsNavItems();
            });

        Event::on(
            SproutVariable::class,
            SproutVariable::EVENT_INIT,
            function(Event $event): void {
                $event->sender->registerModule($this);
            });

        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            function(RegisterUserPermissionsEvent $event): void {
                $event->permissions[] = [
                    'heading' => Craft::t('sprout-module-sent-email', 'Sprout Module | Sent Email'),
                    'permissions' => $this->getUserPermissions(),
                ];
            });

        Event::on(
            Elements::class,
            Elements::EVENT_REGISTER_ELEMENT_TYPES,
            static function(RegisterComponentTypesEvent $event): void {
                $event->types[] = SentEmailElement::class;
            }
        );

        Event::on(
            BaseMailer::class,
            BaseMailer::EVENT_AFTER_SEND, [
            $this->sentEmails, 'handleLogSentEmail',
        ]);
    }

    public function createSettingsModel(): SentEmailSettings
    {
        return new SentEmailSettings();
    }

    public function getSettings(): SentEmailSettings
    {
        /** @var SentEmailSettings $settings */
        $settings = SettingsHelper::getSettingsConfig($this, SentEmailSettings::class);

        return $settings;
    }

    public function getCpUrlRules(): array
    {
        return [
            // Sent Emails
            'sprout/email/sent-email' =>
                'sprout-module-sent-email/sent-email/sent-email-index-template',
            'sprout/email' =>
                'sprout-module-core/settings/redirect-nav-item',

            // Settings
            'sprout/settings/sent-email' => [
                'template' => 'sprout-module-sent-email/_settings/sent-email',
            ],

            // Welcome
            'sprout/welcome/sent-email' => [
                'template' => 'sprout-module-sent-email/_admin/welcome',
            ],
            'sprout/upgrade/sent-email' => [
                'template' => 'sprout-module-sent-email/_admin/upgrade',
            ],
        ];
    }

    public function getUserPermissions(): array
    {
        return [
            self::p('viewSentEmail') => [
                'label' => Craft::t('sprout-module-sent-email', 'View Sent Email'),
                'nested' => [
                    self::p('resendSentEmail') => [
                        'label' => Craft::t('sprout-module-sent-email', 'Resend Sent Emails'),
                    ],
                ],
            ],
        ];
    }

    protected function getCraftCpSettingsNavItems(): array
    {
        return [
            'label' => self::getDisplayName(),
            'url' => 'sprout/settings/sent-email',
            'icon' => self::svg('icons/icon.svg'),
        ];
    }

    protected function getSproutCpSettingsNavItems(): array
    {
        return [
            'sent-email' => [
                'label' => self::getDisplayName(),
                'url' => 'sprout/settings/sent-email',
            ],
        ];
    }
}
