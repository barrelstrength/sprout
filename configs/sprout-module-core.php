<?php

use BarrelStrength\Sprout\datastudio\DataStudioModule;
use BarrelStrength\Sprout\forms\FormsModule;

/**
 * Core Module Config Overrides
 *
 * This file is an example template for the available file-based config override settings.
 *
 * To use file-based overrides, copy and rename this file:
 * From: vendor/barrelstrength/sprout/examples/config/sprout-module-core.php
 * To: craft/config/sprout-module-core.php
 *
 * Configure the settings as you desire. This config file can also be
 * setup as a multi-environment config just as Craft's config/general.php
 * and all settings here can be overridden by environment variables.
 *
 * SPROUT_MODULE_CORE_MODULES=[]
 */
return [
    // Caution: Override replaces the entire modules array
    'modules' => [
        DataStudioModule::class => [
            'enabled' => true,
            'alternateName' => '',
        ],
        FormsModule::class => [
            'enabled' => true,
            'alternateName' => '',
        ],
    ],
];
