<?php

namespace BarrelStrength\Sprout\datastudio;

use BarrelStrength\Sprout\core\db\MigrationTrait;
use BarrelStrength\Sprout\core\editions\EditionTrait;
use BarrelStrength\Sprout\core\modules\CpNavHelper;
use BarrelStrength\Sprout\core\modules\Settings;
use BarrelStrength\Sprout\core\modules\SettingsHelper;
use BarrelStrength\Sprout\core\modules\SproutModuleTrait;
use BarrelStrength\Sprout\core\modules\TranslatableTrait;
use BarrelStrength\Sprout\core\Sprout;
use BarrelStrength\Sprout\core\twig\SproutVariable;
use BarrelStrength\Sprout\datastudio\components\audiences\DataSetAudienceType;
use BarrelStrength\Sprout\datastudio\components\datasources\CustomTwigTemplates;
use BarrelStrength\Sprout\datastudio\components\elements\DataSetElement;
use BarrelStrength\Sprout\datastudio\components\relations\FormRelationsHelper;
use BarrelStrength\Sprout\datastudio\components\widgets\NumberWidget;
use BarrelStrength\Sprout\datastudio\datasets\TwigDataSetVariable;
use BarrelStrength\Sprout\datastudio\datasources\DataSource;
use BarrelStrength\Sprout\datastudio\datasources\DataSources;
use BarrelStrength\Sprout\datastudio\visualizations\Visualizations;
use BarrelStrength\Sprout\mailer\audience\Audiences;
use Craft;
use craft\events\DefineFieldLayoutFieldsEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterCpNavItemsEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\models\FieldLayout;
use craft\services\Dashboard;
use craft\services\Elements;
use craft\services\UserPermissions;
use craft\web\UrlManager;
use craft\web\View;
use yii\base\Event;
use yii\base\Module;

/**
 * @property CustomTwigTemplates $customTwigTemplates
 * @property DataSources $dataSources
 * @property Visualizations $visualizations
 */
class DataStudioModule extends Module
{
    use SproutModuleTrait;
    use EditionTrait;
    use MigrationTrait;
    use TranslatableTrait;

    public static function getInstance(): DataStudioModule
    {
        /** @var DataStudioModule $module */
        $module = Sprout::getSproutModule(static::class, 'sprout-module-data-studio');

        return $module;
    }

    public static function getDisplayName(): string
    {
        $displayName = Craft::t('sprout-module-core', 'Data Studio');

        return $displayName;
    }

    public static function getShortName(): string
    {
        return 'data-studio';
    }

    public static function getDescription(): string
    {
        return Craft::t('sprout-module-core', 'Create data sets and run reports');
    }

    public static function getUpgradeMessage(): string
    {
        return Craft::t('sprout-module-core', 'Upgrade to Sprout Data Studio PRO to export and build custom data sources.');
    }

    public function init(): void
    {
        parent::init();

        $this->registerTranslations();

        Craft::setAlias('@BarrelStrength/Sprout/datastudio', __DIR__);

        $this->setComponents([
            'customTwigTemplates' => CustomTwigTemplates::class,
            'dataSources' => DataSources::class,
            'visualizations' => Visualizations::class,
        ]);

        Event::on(
            Settings::class,
            Settings::INTERNAL_SPROUT_EVENT_REGISTER_CP_SETTINGS_NAV_ITEMS,
            function(RegisterCpNavItemsEvent $event): void {
                $groupName = self::getDisplayName();
                $newNavItems = $this->getSproutCpSettingsNavItems();
                $event->navItems = CpNavHelper::mergeSproutCpSettingsNavItems(
                    $event->navItems,
                    $newNavItems,
                    $groupName
                );
            });

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
            Settings::class,
            Settings::INTERNAL_SPROUT_EVENT_REGISTER_CRAFT_CP_SETTINGS_NAV_ITEMS,
            function(RegisterCpNavItemsEvent $event): void {
                $event->navItems['sprout-module-data-studio'] = $this->getCraftCpSettingsNavItems();
            });

        Event::on(
            Settings::class,
            Settings::INTERNAL_SPROUT_EVENT_REGISTER_CRAFT_CP_SIDEBAR_NAV_ITEMS,
            function(RegisterCpNavItemsEvent $event): void {
                $event->navItems[] = $this->getCraftCpSidebarNavItems();
            });

        Event::on(
            View::class,
            View::EVENT_REGISTER_CP_TEMPLATE_ROOTS,
            function(RegisterTemplateRootsEvent $e): void {
                $e->roots['sprout-module-data-studio'] = $this->getBasePath() . DIRECTORY_SEPARATOR . 'templates';
            });

        Event::on(
            SproutVariable::class,
            SproutVariable::EVENT_INIT,
            function(Event $event): void {
                $event->sender->registerModule($this);
                $event->sender->registerVariable('twigDataSet', new TwigDataSetVariable());
            });

        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            function(RegisterUserPermissionsEvent $event): void {
                $event->permissions[] = [
                    'heading' => Craft::t('sprout-module-data-studio', 'Sprout Module | Data Studio'),
                    'permissions' => $this->getUserPermissions(),
                ];
            });

        Event::on(
            Elements::class,
            Elements::EVENT_REGISTER_ELEMENT_TYPES,
            static function(RegisterComponentTypesEvent $event): void {
                $event->types[] = DataSetElement::class;
            });

        Event::on(
            Dashboard::class,
            Dashboard::EVENT_REGISTER_WIDGET_TYPES,
            static function(RegisterComponentTypesEvent $event): void {
                $event->types[] = NumberWidget::class;
            });

        Event::on(
            FieldLayout::class,
            FieldLayout::EVENT_DEFINE_NATIVE_FIELDS,
            static function(DefineFieldLayoutFieldsEvent $event): void {
                DataSetElement::defineNativeFields($event);
            });

        Event::on(
            Audiences::class,
            Audiences::EVENT_REGISTER_AUDIENCE_TYPES,
            static function(RegisterComponentTypesEvent $event): void {
                $event->types[] = DataSetAudienceType::class;
            });

        Event::on(
            FieldLayout::class,
            FieldLayout::EVENT_CREATE_FORM,
            [FormRelationsHelper::class, 'addDataSourceRelationsTab']
        );
    }

    public function createSettingsModel(): DataStudioSettings
    {
        return new DataStudioSettings();
    }

    public function getSettings(): DataStudioSettings
    {
        /** @var DataStudioSettings $settings */
        $settings = SettingsHelper::getSettingsConfig($this, DataStudioSettings::class);

        return $settings;
    }

    protected function getCraftCpSettingsNavItems(): array
    {
        return [
            'label' => self::getDisplayName(),
            'url' => 'sprout/settings/data-studio',
            'icon' => self::svg('icons/icon.svg'),
        ];
    }

    protected function getCraftCpSidebarNavItems(): array
    {
        if (!Craft::$app->getUser()->checkPermission(self::p('accessModule'))) {
            return [];
        }

        return [
            'group' => Craft::t('sprout-module-data-studio', 'Data Studio'),
            'icon' => self::svg('icons/icon-mask.svg'),
            'navItems' => [
                'data-sets' => [
                    'label' => Craft::t('sprout-module-data-studio', 'Data Sets'),
                    'url' => 'sprout/data-studio',
                ],
            ],
        ];
    }

    protected function getSproutCpSettingsNavItems(): array
    {
        return [
            'data-studio' => [
                'label' => self::getDisplayName(),
                'url' => 'sprout/settings/data-studio',
            ],
        ];
    }

    protected function getCpUrlRules(): array
    {
        return [
            // Data Sets
            'sprout/data-studio/edit/<elementId:\d+>' =>
                'elements/edit',
            'sprout/data-studio/new' =>
                'sprout-module-data-studio/data-set/create-data-set',
            'sprout/data-studio/view/<dataSetId:\d+>' =>
                'sprout-module-data-studio/data-set/results-index-template',
            'sprout/data-studio' =>
                'sprout-module-data-studio/data-set/data-set-index-template',

            // Settings
            'sprout/settings/data-studio' => [
                'template' => 'sprout-module-data-studio/_settings/datastudio',
            ],

            // Welcome
            'sprout/welcome/data-studio' => [
                'template' => 'sprout-module-data-studio/_admin/welcome',
            ],
            'sprout/upgrade/data-studio' => [
                'template' => 'sprout-module-data-studio/_admin/upgrade',
            ],
        ];
    }

    protected function getUserPermissions(): array
    {
        $dataSources = self::getInstance()->dataSources->getDataSourceTypes();

        $permissions = [];

        /** @var DataSource $class */
        foreach ($dataSources as $class) {
            $permissions[self::p('viewReports:' . $class)] = [
                'label' => Craft::t('sprout-module-data-studio', 'View reports: "{dataSet}"', [
                    'dataSet' => $class::displayName(),
                ]),
                'info' => Craft::t('sprout-module-data-studio', 'Includes viewing some settings, running reports, and CSV exports.'),
                'nested' => [
                    self::p('editDataSet:' . $class) => [
                        'label' => Craft::t('sprout-module-data-studio', 'Edit data sets', [
                            'dataSet' => $class::displayName(),
                        ]),
                    ],
                ],
            ];
        }

        return $permissions;
    }
}
