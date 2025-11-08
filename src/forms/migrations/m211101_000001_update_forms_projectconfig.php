<?php

namespace BarrelStrength\Sprout\forms\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\ProjectConfig;

/**
 * @role temporary: Craft 4 => 5
 * @schema sprout-module-forms
 */
#[\Deprecated('tag:craftcms/cms:6.0', 'migration will be removed in Craft CMS 6.0')]
class m211101_000001_update_forms_projectconfig extends Migration
{
    public const SPROUT_KEY = 'sprout';
    public const MODULE_ID = 'sprout-module-forms';
    public const OLD_CONFIG_KEY = 'plugins.sprout-forms.settings';
    public const OLD_ACCESSIBLE_FORM_TEMPLATES = 'BarrelStrength\Sprout\forms\components\formtemplates\AccessibleTemplates';

    public function safeUp(): void
    {
        $moduleSettingsKey = self::SPROUT_KEY . '.' . self::MODULE_ID;

        $defaultSettings = [
            'cleanupProbability' => 1000,
            'defaultSidebarTab' => 'submissions',
            'enableEditSubmissionViaFrontEnd' => false,
            'enableSaveData' => true,
            'formMetadata' => [
                [
                    'label' => 'IP Address',
                    'metadatumFormat' => '{craft.app.request.getRemoteIP}',
                ],
                [
                    'label' => 'Referrer URL',
                    'metadatumFormat' => '{craft.app.request.getReferrer}',
                ],
                [
                    'label' => 'User Agent',
                    'metadatumFormat' => '{craft.app.request.getUserAgent}',
                ],
                [
                    'label' => 'UTM Source',
                    'metadatumFormat' => '{craft.app.request.getParam("utm_source")}',
                ],
                [
                    'label' => 'UTM Medium',
                    'metadatumFormat' => '{craft.app.request.getParam("utm_medium")}',
                ],
                [
                    'label' => 'UTM Campaign',
                    'metadatumFormat' => '{craft.app.request.getParam("utm_campaign")}',
                ],
            ],
            'formTypeUid' => self::OLD_ACCESSIBLE_FORM_TEMPLATES,
            'saveSpamToDatabase' => false,
            'spamLimit' => 500,
            'spamRedirectBehavior' => 'redirectAsNormal',
            'captchaSettings' => [
                'BarrelStrength\Sprout\forms\components\captchas\DuplicateCaptcha' => [
                    'enabled' => false,
                ],
                'BarrelStrength\Sprout\forms\components\captchas\HoneypotCaptcha' => [
                    'enabled' => false,
                    'honeypotFieldName' => 'sprout-forms-hc',
                    'honeypotScreenReaderMessage' => 'Leave this field blank',
                ],
                'BarrelStrength\Sprout\forms\components\captchas\JavascriptCaptcha' => [
                    'enabled' => true,
                ],
            ],
        ];

        $oldConfig = Craft::$app->getProjectConfig()->get(self::OLD_CONFIG_KEY) ?? [];
        $oldConfig = ProjectConfig::unpackAssociativeArray($oldConfig);

        $newConfigExists = Craft::$app->getProjectConfig()->get($moduleSettingsKey);

        if (empty($oldConfig) && $newConfigExists) {
            return;
        }

        $newConfig = [];

        foreach ($defaultSettings as $key => $defaultValue) {
            $newConfig[$key] = isset($oldConfig[$key]) && !empty($oldConfig[$key]) ? $oldConfig[$key] : $defaultValue;
        }

        unset($newConfig['enableSaveDataDefaultValue']);

        foreach ($newConfig['captchaSettings'] as $key => $captchaSettings) {
            if ($newConfig['captchaSettings'][$key]['enabled'] === '1') {
                $newConfig['captchaSettings'][$key]['enabled'] = true;
            }

            if ($newConfig['captchaSettings'][$key]['enabled'] === '') {
                $newConfig['captchaSettings'][$key]['enabled'] = false;
            }
        }

        Craft::$app->getProjectConfig()->set($moduleSettingsKey, $newConfig,
            'Update Sprout Settings for: ' . $moduleSettingsKey
        );

        Craft::$app->getProjectConfig()->remove(self::OLD_CONFIG_KEY);
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
