<?php

/**
 * Meta Module Config Overrides
 *
 * This file is an example template for the available file-based config override settings.
 *
 * To use file-based overrides, copy and rename this file:
 * From: vendor/barrelstrength/sprout/examples/config/sprout-module-meta.php
 * To: craft/config/sprout-module-meta.php
 *
 * Configure the settings as you desire. This config file can also be
 * setup as a multi-environment config just as Craft's config/general.php
 * and all settings here can be overridden by environment variables.
 *
 * SPROUT_MODULE_META_ENABLE_RENDER_METADATA=true
 * SPROUT_MODULE_META_USE_METADATA_VARIABLE=false
 * SPROUT_MODULE_META_METADATA_VARIABLE_NAME='metadata'
 * SPROUT_MODULE_META_MAX_META_DESCRIPTION_LENGTH=160
 */
return [
    'enableRenderMetadata' => true,
    'useMetadataVariable' => false,
    'metadataVariableName' => 'metadata',
    'maxMetaDescriptionLength' => 160,
];
