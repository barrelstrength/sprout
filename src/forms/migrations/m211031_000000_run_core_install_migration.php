<?php

namespace BarrelStrength\Sprout\forms\migrations;

use BarrelStrength\Sprout\core\Sprout;
use craft\db\Migration;

class m211031_000000_run_core_install_migration extends Migration
{
    public function safeUp(): void
    {
        // Ensure Sprout Core Migrations have run during Craft 4 upgrade
        $migrator = Sprout::getInstance()->getMigrator();
        $migrator->up();
    }

    public function safeDown(): bool
    {
        echo "m211031_000000_run_core_install_migration cannot be reverted.\n";

        return false;
    }
}
