<?php

/**
 * Sent Email Module Config Overrides
 *
 * This file is an example template for the available file-based config override settings.
 *
 * To use file-based overrides, copy and rename this file:
 * From: vendor/barrelstrength/sprout/examples/config/sprout-module-sent-email.php
 * To: craft/config/sprout-module-sent-email.php
 *
 * Configure the settings as you desire. This config file can also be
 * setup as a multi-environment config just as Craft's config/general.php
 * and all settings here can be overridden by environment variables.
 *
 * SPROUT_MODULE_SENT_EMAIL_SENT_EMAILS_LIMIT=5000
 * SPROUT_MODULE_SENT_EMAIL_CLEANUP_PROBABILITY=1000
 */
return [
    'sentEmailsLimit' => 5000,
    'cleanupProbability' => 1000,
];
