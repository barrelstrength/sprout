<?php

namespace BarrelStrength\Sprout\mailer\migrations;

use craft\db\Migration;
use Craft;
use craft\helpers\ProjectConfig;

class m230910_000000_remove_mailer_field_layout_settings extends Migration
{
    public const MAILERS_SETTINGS_KEY = 'sprout.sprout-module-mailer.mailers';

    public function safeUp(): void
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $mailers = $projectConfig->get(self::MAILERS_SETTINGS_KEY);

        $mailerConfigs = ProjectConfig::unpackAssociativeArray($mailers);
        foreach ($mailerConfigs as $key => $mailerConfig) {
            unset($mailerConfigs[$key]['fieldLayouts']);
        }

        $mailers = ProjectConfig::packAssociativeArray($mailerConfigs);
        $projectConfig->set(self::MAILERS_SETTINGS_KEY, $mailers);
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
