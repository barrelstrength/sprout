<?php

namespace BarrelStrength\Sprout\core\modules;

use BarrelStrength\Sprout\core\db\SproutPluginMigrationInterface;
use BarrelStrength\Sprout\core\Sprout;
use Craft;
use Illuminate\Support\Collection;

class CpNavHelper
{
    /**
     * Updates Craft's CP sidebar navigation to include nav items for Sprout modules
     *
     * Add $hasCpSection to any Sprout plugin that uses modules that should be added to the CP sidebar navigation.
     */
    public static function getUpdatedCpNavItems(array $cpNavItems): array
    {
        $plugins = Craft::$app->getPlugins()->getAllPlugins();

        $pluginsWithCpSections = array_filter($plugins, static function($plugin) {
            return $plugin->hasCpSection;
        });

        // We have to enable $hasCpSection in each plugin then remove the default output just in case no other plugins with CP sections are installed
        $cpNavSproutPluginNavKeys = array_keys(array_filter($pluginsWithCpSections, static function($plugin) {
            return $plugin instanceof SproutPluginMigrationInterface;
        }));

        // get the nav items of the plugins with cp sections from the $cpNavItems based on the $pluginsWithCpSections matching the url to the plugin handle
        $cpNavOldPluginNavItems = array_filter($cpNavItems, static function($navItem) use ($pluginsWithCpSections) {
            foreach ($pluginsWithCpSections as $plugin) {
                $cpNavItem = $plugin->getCpNavItem();
                $pluginNavItemUrl = $cpNavItem['url'] ?? null;
                if ($navItem['url'] === $pluginNavItemUrl) {
                    return true;
                }
            }

            return false;
        });

        $cpNavSproutModuleNavItems = [];

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
                $firstSubNavItem = array_slice($sproutNavGroup['subnav'], 0, 1);
                // reset keys so we can access via array item 0
                $firstSubNavItem = array_values($firstSubNavItem);
                // get subnav item URL so we don't need to redirect and figure it out.
                $firstSubNavItemUrl = $firstSubNavItem[0]['url'] ?? null;

                if ($firstSubNavItemUrl) {
                    $sproutNavGroup['url'] = $firstSubNavItemUrl;
                }

                unset($sproutNavGroup['subnav']);
            }

            $cpNavSproutModuleNavItems[] = $sproutNavGroup;
        }

        $cpNavNewPluginNavItems = array_merge($cpNavOldPluginNavItems, $cpNavSproutModuleNavItems);

        $cpNavNewPluginNavItems = array_filter($cpNavNewPluginNavItems, static function($navItem) use ($cpNavSproutPluginNavKeys) {
            foreach ($cpNavSproutPluginNavKeys as $sproutNavKey) {
                if ($navItem['url'] === $sproutNavKey) {
                    return false;
                }
            }

            return true;
        });

        uasort($cpNavNewPluginNavItems, static fn($a, $b) => $a['label'] <=> $b['label']);

        // Remove all the Sprout Plugin hasCpSection nav items
        $newCpNavItems = array_filter($cpNavItems, static function($navItem) use ($pluginsWithCpSections) {
            foreach ($pluginsWithCpSections as $plugin) {
                $cpNavItem = $plugin->getCpNavItem();
                $pluginNavItemUrl = $cpNavItem['url'] ?? null;
                if ($navItem['url'] === $pluginNavItemUrl) {
                    return false;
                }
            }

            return true;
        });

        // If no other plugins are installed and no modules are enabled, this will be the Sprout plugin and just get removed later
        $firstPlugin = reset($pluginsWithCpSections);
        $firstCpNavItem = $firstPlugin->getCpNavItem();
        $firstPluginNavItemUrl = $firstCpNavItem['url'] ?? null;
        $cpNavFirstPluginItemIndex = Collection::make($cpNavItems)->search(fn(array $item) => $item['url'] === $firstPluginNavItemUrl);

        // If we don't find any plugins with CP sections (a Sprout Plugin should always be there), we'll just add the Sprout Modules to the end of the nav
        if ($cpNavFirstPluginItemIndex === false) {
            $cpNavFirstPluginItemIndex = count($newCpNavItems);
        }

        // Insert the Sprout Module nav items
        array_splice($newCpNavItems, $cpNavFirstPluginItemIndex, 0, $cpNavNewPluginNavItems);

        return $newCpNavItems;
    }

    /**
     * Adds nav items for a give module to the Sprout settings sidebar navigation
     */
    public static function mergeSproutCpSettingsNavItems(
        array $oldNavItems,
        array $newNavItems,
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
