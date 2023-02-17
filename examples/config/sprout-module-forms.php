<?php

use BarrelStrength\Sprout\forms\components\captchas\DuplicateCaptcha;
use BarrelStrength\Sprout\forms\components\formtemplates\DefaultFormTemplateSet;
use BarrelStrength\Sprout\forms\FormsSettings;

/**
 * Forms Module Config Overrides
 *
 * This file is an example template for the available file-based config override settings.
 *
 * To use file-based overrides, copy and rename this file:
 * From: vendor/barrelstrength/sprout/examples/config/sprout-module-forms.php
 * To: craft/config/sprout-module-forms.php
 *
 * Configure the settings as you desire. This config file can also be
 * setup as a multi-environment config just as Craft's config/general.php
 * and all settings here can be overridden by environment variables.
 *
 * SPROUT_MODULE_FORMS_DEFAULT_SECTION='entries'
 * SPROUT_MODULE_FORMS_FORM_TEMPLATE_ID='BarrelStrength\Sprout\forms\components\formtemplates\DefaultFormTemplateSet'
 * SPROUT_MODULE_FORMS_ENABLE_SAVE_DATA=true
 * SPROUT_MODULE_FORMS_SAVE_SPAM_TO_DATABASE=false
 * SPROUT_MODULE_FORMS_ENABLE_SAVE_DATA_DEFAULT_VALUE=true
 * SPROUT_MODULE_FORMS_SPAM_REDIRECT_BEHAVIOR='redirectAsNormal'
 * SPROUT_MODULE_FORMS_SPAM_LIMIT=500
 * SPROUT_MODULE_FORMS_CLEANUP_PROBABILITY=1000
 * SPROUT_MODULE_FORMS_TRACK_REMOTE_IP=false
 * SPROUT_MODULE_FORMS_ENABLE_EDIT_SUBMISSION_VIA_FRONT_END=false
 * SPROUT_MODULE_FORMS_CAPTCHA_SETTINGS=[]
 */
return [
    'defaultSection' => 'entries',
    'formTemplateId' => DefaultFormTemplateSet::class,
    'enableSaveData' => true,
    'saveSpamToDatabase' => false,
    'enableSaveDataDefaultValue' => true,
    'spamRedirectBehavior' => FormsSettings::SPAM_REDIRECT_BEHAVIOR_NORMAL,
    'spamLimit' => 500,
    'cleanupProbability' => 1000,
    'trackRemoteIp' => false,
    'enableEditSubmissionViaFrontEnd' => false,
    'captchaSettings' => [
        DuplicateCaptcha::class => [
            'enabled' => false,
        ],
    ],
];
