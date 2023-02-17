<?php

namespace BarrelStrength\Sprout\mailer\migrations;

use Craft;
use craft\db\Migration;

class m211101_000001_update_lists_projectconfig extends Migration
{
    public const SPROUT_KEY = 'sprout';
    public const MODULE_ID = 'sprout-module-mailer';
    public const OLD_CONFIG_KEY = 'plugins.sprout-lists.settings';

    public function safeUp(): void
    {
        $moduleSettingsKey = self::SPROUT_KEY . '.' . self::MODULE_ID;

        $defaultSettings = [
            'enableAutoList' => false,
            'enableUserSync' => false,
        ];

        $oldConfig = Craft::$app->getProjectConfig()->get(self::OLD_CONFIG_KEY) ?? [];
        $newConfig = [];

        foreach ($defaultSettings as $key => $defaultValue) {
            $newConfig[$key] = isset($oldConfig[$key]) ? $oldConfig[$key] ?? $defaultValue : $defaultValue;
        }

        // Ensure proper data types
        if ($newConfig['enableAutoList'] === '1') {
            $newConfig['enableAutoList'] = true;
        }

        if ($newConfig['enableAutoList'] === '') {
            $newConfig['enableAutoList'] = false;
        }

        if ($newConfig['enableUserSync'] === '1') {
            $newConfig['enableUserSync'] = true;
        }

        if ($newConfig['enableUserSync'] === '') {
            $newConfig['enableUserSync'] = false;
        }

        Craft::$app->getProjectConfig()->set($moduleSettingsKey, $newConfig,
            "Update Sprout Settings for “{$moduleSettingsKey}”"
        );

        Craft::$app->getProjectConfig()->remove(self::OLD_CONFIG_KEY);
    }

    public function safeDown(): bool
    {
        echo "m211101_000001_update_lists_projectconfig cannot be reverted.\n";

        return false;
    }
}
