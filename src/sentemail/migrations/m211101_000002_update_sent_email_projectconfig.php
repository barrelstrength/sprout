<?php

namespace BarrelStrength\Sprout\sentemail\migrations;

use Craft;
use craft\db\Migration;

class m211101_000002_update_sent_email_projectconfig extends Migration
{
    public const SPROUT_KEY = 'sprout';
    public const MODULE_ID = 'sprout-module-sent-email';
    public const OLD_CONFIG_KEY = 'plugins.sprout-sent-email.settings';

    public function safeUp(): void
    {
        $moduleSettingsKey = self::SPROUT_KEY . '.' . self::MODULE_ID;

        $defaultSettings = [
            'cleanupProbability' => 1000,
            'sentEmailsLimit' => 5000,
        ];

        $oldConfig = Craft::$app->getProjectConfig()->get(self::OLD_CONFIG_KEY) ?? [];
        $newConfig = [];

        foreach ($defaultSettings as $key => $defaultValue) {
            $newConfig[$key] = isset($oldConfig[$key]) ? $oldConfig[$key] ?? $defaultValue : $defaultValue;
        }

        // Ensure proper data type
        if (!is_int($newConfig['cleanupProbability'])) {
            $newConfig['cleanupProbability'] = (int)$newConfig['cleanupProbability'];
        }

        if (!is_int($newConfig['sentEmailsLimit'])) {
            $newConfig['sentEmailsLimit'] = (int)$newConfig['sentEmailsLimit'];
        }

        Craft::$app->getProjectConfig()->set($moduleSettingsKey, $newConfig,
            "Update Sprout Settings for “{$moduleSettingsKey}”"
        );

        Craft::$app->getProjectConfig()->remove(self::OLD_CONFIG_KEY);
    }

    public function safeDown(): bool
    {
        echo "m211101_000002_update_sent_email_projectconfig cannot be reverted.\n";

        return false;
    }
}
