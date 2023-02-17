<?php

use BarrelStrength\Sprout\sitemaps\SitemapsSettings;

/**
 * Sitemaps Module Config Overrides
 *
 * This file is an example template for the available file-based config override settings.
 *
 * To use file-based overrides, copy and rename this file:
 * From: vendor/barrelstrength/sprout/examples/config/sprout-module-sitemaps.php
 * To: craft/config/sprout-module-sitemaps.php
 *
 * Configure the settings as you desire. This config file can also be
 * setup as a multi-environment config just as Craft's config/general.php
 * and all settings here can be overridden by environment variables.
 *
 * SPROUT_MODULE_SITEMAPS_ENABLE_CUSTOM_SECTIONS=false
 * SPROUT_MODULE_SITEMAPS_TOTAL_ELEMENTS_PER_SITEMAP=500
 * SPROUT_MODULE_SITEMAPS_SITEMAP_AGGREGATION_METHOD='singleLanguageSitemaps'
 */
return [
    'enableCustomSections' => false,
    'totalElementsPerSitemap' => 500,
    'sitemapAggregationMethod' => SitemapsSettings::AGGREGATION_METHOD_SINGLE_LANGUAGE,
];
