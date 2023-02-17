<?php

namespace BarrelStrength\Sprout\sitemaps\migrations;

use Craft;
use craft\db\Migration;

class m211101_000002_update_sitemaps_projectconfig extends Migration
{
    public const SPROUT_KEY = 'sprout';
    public const MODULE_ID = 'sprout-module-sitemaps';
    public const MODULE_CLASS = 'BarrelStrength\Sprout\sitemaps\SitemapsModule';
    public const AGGREGATION_METHOD_SINGLE_LANGUAGE = 'singleLanguageSitemaps';
    public const AGGREGATION_METHOD_MULTI_LINGUAL = 'multiLingualSitemaps';
    public const OLD_CONFIG_KEY = 'plugins.sprout-sitemaps.settings';

    public function safeUp(): void
    {
        $moduleSettingsKey = self::SPROUT_KEY . '.' . self::MODULE_ID;

        $primarySite = Craft::$app->getSites()->getPrimarySite();

        $defaultSettings = [
            'enableCustomSections' => false,
            'enableMultilingualSitemaps' => false,
            'totalElementsPerSitemap' => 500,
            'sitemapAggregationMethod' => self::AGGREGATION_METHOD_SINGLE_LANGUAGE,
            'siteSettings' => [$primarySite->id],
            'groupSettings' => [$primarySite->groupId],
        ];

        $oldConfig = Craft::$app->getProjectConfig()->get(self::OLD_CONFIG_KEY) ?? [];
        $newConfig = [];

        foreach ($defaultSettings as $key => $defaultValue) {
            $newConfig[$key] = isset($oldConfig[$key]) ? $oldConfig[$key] ?? $defaultValue : $defaultValue;
        }

        // Just in case
        if (empty($newConfig['siteSettings'])) {
            $newConfig['siteSettings'][$primarySite->id] = $primarySite->id;
        }

        // Ensure proper data types
        if (!is_int($newConfig['totalElementsPerSitemap'])) {
            $newConfig['totalElementsPerSitemap'] = (int)$newConfig['totalElementsPerSitemap'];
        }

        if ($newConfig['enableCustomSections'] === '1') {
            $newConfig['enableCustomSections'] = true;
        }

        if ($newConfig['enableCustomSections'] === '') {
            $newConfig['enableCustomSections'] = false;
        }

        if ($newConfig['enableMultilingualSitemaps'] === '1') {
            $newConfig['sitemapAggregationMethod'] = self::AGGREGATION_METHOD_SINGLE_LANGUAGE;
        }

        if ($newConfig['enableMultilingualSitemaps'] === '') {
            $newConfig['sitemapAggregationMethod'] = self::AGGREGATION_METHOD_MULTI_LINGUAL;
        }

        // enableMultiLingualSitemaps => sitemapAggregationMethod
        unset($newConfig['enableMultiLingualSitemaps']);

        Craft::$app->getProjectConfig()->set($moduleSettingsKey, $newConfig,
            "Update Sprout Settings for “{$moduleSettingsKey}”"
        );

        Craft::$app->getProjectConfig()->remove(self::OLD_CONFIG_KEY);
    }

    public function safeDown(): bool
    {
        echo "m211101_000002_update_sitemaps_projectconfig cannot be reverted.\n";

        return false;
    }
}
