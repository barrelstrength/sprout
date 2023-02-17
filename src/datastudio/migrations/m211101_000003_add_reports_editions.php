<?php

namespace BarrelStrength\Sprout\datastudio\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\App;

class m211101_000003_add_reports_editions extends Migration
{
    public function safeUp(): void
    {
        // Don't make the same config changes twice
        $projectConfig = Craft::$app->getProjectConfig();
        $schemaVersion = $projectConfig->get('plugins.sprout-reports.schemaVersion', true);

        if (version_compare($schemaVersion, '4.44.444', '>=')) {
            return;
        }

        $edition = App::editionHandle(Craft::Pro);
        Craft::$app->getPlugins()->switchEdition('sprout-data-studio', $edition);
    }

    public function safeDown(): bool
    {
        echo "m211101_000003_add_reports_editions cannot be reverted.\n";

        return false;
    }
}
