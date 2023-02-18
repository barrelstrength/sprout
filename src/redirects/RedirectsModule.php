<?php

namespace BarrelStrength\Sprout\redirects;

use BarrelStrength\Sprout\core\db\MigrationTrait;
use BarrelStrength\Sprout\core\editions\EditionTrait;
use BarrelStrength\Sprout\core\modules\CpNavHelper;
use BarrelStrength\Sprout\core\modules\Settings;
use BarrelStrength\Sprout\core\modules\SproutModuleTrait;
use BarrelStrength\Sprout\core\modules\TranslatableTrait;
use BarrelStrength\Sprout\core\Sprout;
use BarrelStrength\Sprout\core\twig\SproutVariable;
use BarrelStrength\Sprout\redirects\components\elements\conditions\MatchStrategyConditionRule;
use BarrelStrength\Sprout\redirects\components\elements\conditions\StatusCodeConditionRule;
use BarrelStrength\Sprout\redirects\components\elements\RedirectElement;
use BarrelStrength\Sprout\redirects\redirects\Redirects;
use Craft;
use craft\base\conditions\BaseCondition;
use craft\config\BaseConfig;
use craft\db\Query;
use craft\db\Table;
use craft\events\DefineFieldLayoutFieldsEvent;
use craft\events\ExceptionEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterConditionRuleTypesEvent;
use craft\events\RegisterCpNavItemsEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\models\FieldLayout;
use craft\services\Elements;
use craft\services\UserPermissions;
use craft\web\ErrorHandler;
use craft\web\UrlManager;
use craft\web\View;
use yii\base\Event;
use yii\base\Module;

/**
 * @property Redirects $redirects
 */
class RedirectsModule extends Module
{
    use SproutModuleTrait;
    use EditionTrait;
    use MigrationTrait;
    use TranslatableTrait;

    public static function getInstance(): RedirectsModule
    {
        /** @var RedirectsModule $module */
        $module = Sprout::getSproutModule(static::class, 'sprout-module-redirects');

        return $module;
    }

    public static function getDisplayName(): string
    {
        $displayName = Craft::t('sprout-module-core', 'Redirects');

        return $displayName;
    }

    public static function getShortName(): string
    {
        return 'redirects';
    }

    public static function getDescription(): string
    {
        return Craft::t('sprout-module-core', 'Manage redirects and track 404s');
    }

    public static function getUpgradeMessage(): string
    {
        return Craft::t('sprout-module-core', 'Upgrade to Sprout Redirects PRO to manage Unlimited Redirects.');
    }

    public function init(): void
    {
        parent::init();

        $this->registerTranslations();

        $this->setComponents([
            'redirects' => Redirects::class,
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
                $e->roots['sprout-module-redirects'] = $this->getBasePath() . DIRECTORY_SEPARATOR . 'templates';
            });

        Event::on(
            Settings::class,
            Settings::EVENT_REGISTER_SPROUT_CP_SETTINGS_NAV_ITEMS,
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
            Settings::EVENT_REGISTER_SPROUT_CRAFT_CP_SIDEBAR_NAV_ITEMS,
            function(RegisterCpNavItemsEvent $event): void {
                $event->navItems[] = $this->getCraftCpSidebarNavItems();
            });

        Event::on(
            Settings::class,
            Settings::EVENT_REGISTER_SPROUT_CRAFT_CP_SETTINGS_NAV_ITEMS,
            function(RegisterCpNavItemsEvent $event): void {
                $event->navItems['sprout-module-redirects'] = $this->getCraftCpSettingsNavItems();
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
                    'heading' => 'Sprout Module | Redirects',
                    'permissions' => $this->getUserPermissions(),
                ];
            });

        Event::on(
            Elements::class,
            Elements::EVENT_REGISTER_ELEMENT_TYPES,
            static function(RegisterComponentTypesEvent $event): void {
                $event->types[] = RedirectElement::class;
            }
        );

        Event::on(
            BaseCondition::class,
            BaseCondition::EVENT_REGISTER_CONDITION_RULE_TYPES,
            static function(RegisterConditionRuleTypesEvent $event): void {
                $event->conditionRuleTypes[] = MatchStrategyConditionRule::class;
                $event->conditionRuleTypes[] = StatusCodeConditionRule::class;
            });

        Event::on(
            ErrorHandler::class,
            ErrorHandler::EVENT_BEFORE_HANDLE_EXCEPTION,
            static function(ExceptionEvent $event): void {
                RedirectsModule::getInstance()->redirects->handleRedirectsOnException($event);
            });

        Event::on(
            FieldLayout::class,
            FieldLayout::EVENT_DEFINE_NATIVE_FIELDS,
            static function(DefineFieldLayoutFieldsEvent $event): void {
                RedirectElement::defineNativeFields($event);
            }
        );

        $uid = (new Query)
            ->select('uid')
            ->from(Table::STRUCTURES)
            ->where([
                'id' => 7,
            ])
            ->scalar();

        //$key = self::projectConfigPath('structureUid');
        //Craft::$app->getProjectConfig()->set($key, $uid);
        //die('Shazam!');
    }

    public function createSettingsModel(): RedirectsSettings
    {
        return new RedirectsSettings();
    }

    public function getSettings(): RedirectsSettings|BaseConfig
    {
        return Settings::getSettingsConfig($this, RedirectsSettings::class);
    }

    protected function getCraftCpSidebarNavItems(): array
    {
        if (!Craft::$app->getUser()->checkPermission(self::p('accessModule'))) {
            return [];
        }

        return [
            'group' => Craft::t('sprout-module-redirects', 'Redirects'),
            'icon' => self::svg('icons/icon-mask.svg'),
            'navItems' => [
                'redirects' => [
                    'label' => Craft::t('sprout-module-redirects', 'Redirects'),
                    'url' => 'sprout/redirects',
                ],
            ],
        ];
    }

    protected function getCraftCpSettingsNavItems(): array
    {
        return [
            'label' => self::getDisplayName(),
            'url' => 'sprout/settings/redirects',
            'icon' => self::svg('icons/icon.svg'),
        ];
    }

    protected function getSproutCpSettingsNavItems(): array
    {
        return [
            'redirects' => [
                'label' => self::getDisplayName(),
                'url' => 'sprout/settings/redirects',
            ],
        ];
    }

    protected function getCpUrlRules(): array
    {
        return [
            'sprout/redirects/edit/<elementId:\d+>' =>
                'elements/edit',
            'sprout/redirects/new' =>
                'sprout-module-redirects/redirects/create-redirect',
            'sprout/redirects' =>
                'sprout-module-redirects/redirects/index-template',

            // DB Settings
            'sprout/redirects/settings' =>
                'sprout-module-redirects/redirects/settings-template',

            // Project Config Settings
            'sprout/settings/redirects' => [
                'template' => 'sprout-module-redirects/_settings/redirects',
            ],

            // Welcome
            'sprout/welcome/redirects' => [
                'template' => 'sprout-module-redirects/_admin/welcome',
            ],
            'sprout/upgrade/redirects' => [
                'template' => 'sprout-module-redirects/_admin/upgrade',
            ],
        ];
    }

    protected function getUserPermissions(): array
    {
        return [
            self::p('editRedirects') => [
                'label' => Craft::t('sprout-module-redirects', 'Edit Redirects'),
            ],
        ];
    }
}
