<?php

namespace BarrelStrength\Sprout\core\modules;

use BarrelStrength\Sprout\core\Sprout;
use Craft;
use craft\events\RegisterCpNavItemsEvent;
use craft\helpers\UrlHelper;
use yii\base\Component;

class Settings extends Component
{
    public const INTERNAL_SPROUT_EVENT_REGISTER_CRAFT_CP_SETTINGS_NAV_ITEMS = 'registerSproutCraftCpSettingsNavItems';

    public const INTERNAL_SPROUT_EVENT_REGISTER_CRAFT_CP_SIDEBAR_NAV_ITEMS = 'registerSproutCraftCpSidebarNavItems';

    public const INTERNAL_SPROUT_EVENT_REGISTER_CP_SETTINGS_NAV_ITEMS = 'registerSproutCpSettingsNavItems';

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

        $this->trigger(self::INTERNAL_SPROUT_EVENT_REGISTER_CRAFT_CP_SIDEBAR_NAV_ITEMS, $event);

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

        $this->trigger(self::INTERNAL_SPROUT_EVENT_REGISTER_CRAFT_CP_SETTINGS_NAV_ITEMS, $event);

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

        $this->trigger(self::INTERNAL_SPROUT_EVENT_REGISTER_CP_SETTINGS_NAV_ITEMS, $event);

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
}
