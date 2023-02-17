<?php

namespace BarrelStrength\Sprout\core\modules;

use BarrelStrength\Sprout\core\Sprout;

class CpNavHelper
{
    /**
     * Updates Craft's CP sidebar navigation to include nav items for Sprout modules
     */
    public static function getUpdatedCpNavItems(array $cpNavItems): array
    {
        $beforePluginNavItemKeys = [
            'dashboard',
            'entries',
            'globals',
            'categories',
            'assets',
            'users',
        ];

        $afterPluginNavItemKeys = [
            'graphql',
            'utilities',
            'settings',
            'plugin-store',
        ];

        $newCpNavItems = [];
        $afterCpNavItems = [];
        $otherCpNavItems = [];

        // Break out the current nav into multiple arrays that we can re-assemble later
        // 1. Craft defaults at the top of the nav
        // 2. Plugins and stuff
        // 3. Craft defaults and settings at bottom of nav
        foreach ($cpNavItems as $cpNavItem) {
            switch (true) {
                case in_array($cpNavItem['url'], $beforePluginNavItemKeys, true):
                    $newCpNavItems[] = $cpNavItem;
                    break;

                case in_array($cpNavItem['url'], $afterPluginNavItemKeys, true):
                    $afterCpNavItems[] = $cpNavItem;
                    break;
                default:
                    $otherCpNavItems[] = $cpNavItem;
                    break;
            }
        }

        $sproutNavGroupsInfo = Sprout::getInstance()->coreSettings->getCraftCpSidebarNavItems();
        $sproutNavGroups = [];

        // Add our module nav items to the plugins and stuff
        foreach ($sproutNavGroupsInfo as $sproutNavGroupInfo) {

            if (!isset($sproutNavGroupInfo['navItems'])) {
                continue;
            }

            // Prepare subnav items
            foreach ($sproutNavGroupInfo['navItems'] as $key => $navItem) {
                $sproutNavGroups[$sproutNavGroupInfo['group']]['subnav'][$key] = [
                    'label' => $navItem['label'],
                    'url' => $navItem['url'],
                    'sortOrder' => $navItem['sortOrder'] ?? 99,
                ];
            }

            uasort($sproutNavGroups[$sproutNavGroupInfo['group']]['subnav'],
                static fn($a, $b) => $a['sortOrder'] - $b['sortOrder']);

            $firstSubNavItem = array_slice(
                $sproutNavGroups[$sproutNavGroupInfo['group']]['subnav'], 0, 1
            );

            // URL of first subnav
            $firstSubnavUrl = array_column($firstSubNavItem, 'url');

            // Prepare main nav item
            $sproutNavGroups[$sproutNavGroupInfo['group']]['label'] = $sproutNavGroupInfo['group'];
            $sproutNavGroups[$sproutNavGroupInfo['group']]['icon'] = $sproutNavGroupInfo['icon'];
            $sproutNavGroups[$sproutNavGroupInfo['group']]['url'] = $sproutNavGroupInfo['url'] ?? $firstSubnavUrl[0];
        }

        foreach ($sproutNavGroups as $sproutNavGroup) {
            // No need for a subnav if we just have one item
            if (count($sproutNavGroup['subnav']) === 1) {
                unset($sproutNavGroup['subnav']);
            }

            $otherCpNavItems[] = $sproutNavGroup;
        }

        // Sort custom nav items alphabetically by label
        uasort($otherCpNavItems, static fn($a, $b) => $a['label'] <=> $b['label']);

        // Add the custom nav items back to the nav
        foreach ($otherCpNavItems as $otherCpNavItem) {
            $newCpNavItems[] = $otherCpNavItem;
        }

        // Add the Craft defaults back to the bottom of the nav
        foreach ($afterCpNavItems as $afterCpNavItem) {
            $newCpNavItems[] = $afterCpNavItem;
        }

        return $newCpNavItems;
    }

    /**
     * Adds nav items for a give module to the Sprout settings sidebar navigation
     */
    public static function mergeSproutCpSettingsNavItems(
        array  $oldNavItems,
        array  $newNavItems,
        string $groupName,
    ): array {
        $navItems = $oldNavItems;

        if (isset($oldNavItems[$groupName])) {
            $navItems[$groupName] = array_merge($oldNavItems[$groupName], $newNavItems);
        } else {
            $navItems[$groupName] = $newNavItems;
        }

        return $navItems;
    }

    /**
     * Adds a section for Sprout module settings to the Craft Settings page
     */
    public static function getUpdatedCraftCpSettingsItems(array $cpSettings): array
    {
        return $cpSettings + [
                'Sprout' => Sprout::getInstance()->coreSettings->getCraftCpSettingsNavItems(),
            ];
    }
}
