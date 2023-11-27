<?php

namespace BarrelStrength\Sprout\core\db;

use craft\base\Plugin;
use craft\db\MigrationManager;

interface MigrationInterface
{
    public function getMigrator(): MigrationManager;
}
