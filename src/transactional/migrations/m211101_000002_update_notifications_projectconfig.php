<?php

namespace BarrelStrength\Sprout\transactional\migrations;

use Craft;
use craft\db\Migration;

class m211101_000002_update_notifications_projectconfig extends Migration
{
    public const SPROUT_KEY = 'sprout';
    public const MODULE_ID = 'sprout-module-transactional';
    public const OLD_CONFIG_KEY = 'plugins.sprout-email.settings';

    public function safeUp(): void
    {
        $moduleSettingsKey = self::SPROUT_KEY . '.' . self::MODULE_ID;

        $templateIdMap = [
            'barrelstrength\sproutbaseemail\emailtemplates\BasicTemplates' => 'BarrelStrength\Sprout\mailer\components\emailthemes\DefaultEmailTemplates',
            'barrelstrength\sproutbaseemail\emailtemplates\CustomTemplates' => 'BarrelStrength\Sprout\mailer\components\emailthemes\CustomEmailTemplates',
        ];

        $defaultSettings = [
            'emailTemplateId' => 'BarrelStrength\Sprout\mailer\components\emailthemes\DefaultEmailTemplates',
            'enablePerEmailEmailTemplateIdOverride' => false,
        ];

        $oldConfig = Craft::$app->getProjectConfig()->get(self::OLD_CONFIG_KEY) ?? [];
        $newConfig = [];

        foreach ($defaultSettings as $key => $defaultValue) {
            if (isset($oldConfig[$key])) {
                if ($key === 'emailTemplateId') {
                    $newConfig[$key] = $templateIdMap[$oldConfig[$key]] ?? $defaultValue;
                } else {
                    // Grab the existing settings
                    $newConfig[$key] = $oldConfig[$key] ?? $defaultValue;
                }
            } else {
                // Use the default settings
                $newConfig[$key] = $defaultValue;
            }
        }

        // Ensure proper data types
        if ($newConfig['enablePerEmailEmailTemplateIdOverride'] === 1) {
            $newConfig['enablePerEmailEmailTemplateIdOverride'] = true;
        }

        if ($newConfig['enablePerEmailEmailTemplateIdOverride'] === 0) {
            $newConfig['enablePerEmailEmailTemplateIdOverride'] = false;
        }

        Craft::$app->getProjectConfig()->set($moduleSettingsKey, $newConfig,
            'Update Sprout Settings for “' . $moduleSettingsKey . '”'
        );

        Craft::$app->getProjectConfig()->remove(self::OLD_CONFIG_KEY);
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
