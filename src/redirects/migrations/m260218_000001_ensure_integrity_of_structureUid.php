<?php

namespace BarrelStrength\Sprout\redirects\migrations;

use BarrelStrength\Sprout\core\modules\SettingsHelper;
use BarrelStrength\Sprout\redirects\migrations\helpers\RedirectStructureHelper;
use Craft;
use craft\db\Migration;
use craft\errors\ElementNotFoundException;

/**
 * @role permanent
 * @schema sprout-module-redirects
 * @since merge with install migration in craftcms/cms:6.0
 */
class m260218_000001_ensure_integrity_of_structureUid extends Migration
{
    public const SPROUT_KEY = 'sprout';
    public const MODULE_ID = 'sprout-module-redirects';

    /**
     * There is a bug in the install/upgrade path where the structureUid in the Project Config can get out of sync with
     * the structureUid in the database. This migration ensures the structureUid from the project config matches the
     * structureUid in the database.
     */
    public function safeUp(): void
    {
        $moduleSettingsKey = self::SPROUT_KEY . '.' . self::MODULE_ID;

        $projectConfigSettings = Craft::$app->getProjectConfig()->get($moduleSettingsKey);
        $structureUidFromConfig = $projectConfigSettings['structureUid'] ?? null;

        if (!$structureUidFromConfig) {
            throw new ElementNotFoundException('Unable to find Structure Element in Project Config for Redirects.');
        }

        $structureUidInDb = RedirectStructureHelper::getStructureUidFromDb($structureUidFromConfig);

        if (!$structureUidInDb) {
            throw new ElementNotFoundException('Unable to find or create Structure Element for Redirects.');
        }

        if ($structureUidFromConfig !== $structureUidInDb) {
            RedirectStructureHelper::updateStructureInDbToMatchUidInProjectConfig(
                $structureUidFromConfig,
                $structureUidInDb
            );
        }
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
