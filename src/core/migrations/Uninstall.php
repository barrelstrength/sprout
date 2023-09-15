<?php

namespace BarrelStrength\Sprout\core\migrations;

use BarrelStrength\Sprout\core\db\SproutTable;
use Craft;
use craft\db\Migration;

class Uninstall extends Migration
{
    public const OLD_SETTINGS_TABLE = '{{%sprout_settings_craft3}}';
    public const SPROUT_KEY = 'sprout';

    public function safeDown(): void
    {
        $this->dropTableIfExists(SproutTable::SETTINGS);

        // Just in case it wasn't cleaned up in an upgrade with multiple plugins
        $this->dropTableIfExists(self::OLD_SETTINGS_TABLE);

        Craft::$app->getProjectConfig()->remove(self::SPROUT_KEY);
    }
}
