<?php

use BarrelStrength\Sprout\redirects\redirects\MatchDefinition;
use BarrelStrength\Sprout\redirects\redirects\QueryStringStrategy;

/**
 * Redirects Module Config Overrides
 *
 * This file is an example template for the available file-based config override settings.
 *
 * To use file-based overrides, copy and rename this file:
 * From: vendor/barrelstrength/sprout/examples/config/sprout-module-redirects.php
 * To: craft/config/sprout-module-redirects.php
 *
 * Configure the settings as you desire. This config file can also be
 * setup as a multi-environment config just as Craft's config/general.php
 * and all settings here can be overridden by environment variables.
 *
 * SPROUT_MODULE_REDIRECTS_ENABLE_404_REDIRECT_LOG=false
 * SPROUT_MODULE_REDIRECTS_TOTAL_404_REDIRECTS=250
 * SPROUT_MODULE_REDIRECTS_TRACK_REMOTE_IP=true
 * SPROUT_MODULE_REDIRECTS_MATCH_DEFINITION='urlWithoutQueryStrings'
 * SPROUT_MODULE_REDIRECTS_QUERY_STRING_STRATEGY='removeQueryStrings'
 */
return [
    'enable404RedirectLog' => false,
    'total404Redirects' => 250,
    'trackRemoteIp' => true,
    'matchDefinition' => MatchDefinition::URL_WITHOUT_QUERY_STRINGS,
    'queryStringStrategy' => QueryStringStrategy::REMOVE_QUERY_STRINGS,
    'cleanupProbability' => 1000,
];
