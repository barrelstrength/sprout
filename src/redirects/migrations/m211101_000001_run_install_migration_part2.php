<?php

namespace BarrelStrength\Sprout\redirects\migrations;

use BarrelStrength\Sprout\redirects\migrations\helpers\RedirectStructureHelper;
use Craft;
use craft\db\Migration;
use craft\errors\ElementNotFoundException;

/**
 * @role permanent
 * @schema sprout-module-redirects
 * @since 5.0.3
 */
class m211101_000001_run_install_migration_part2 extends Migration
{
    public const SPROUT_KEY = 'sprout';
    public const MODULES_KEY = self::SPROUT_KEY . '.sprout-module-core.modules';
    public const MODULE_ID = 'sprout-module-redirects';
    public const MODULE_CLASS = 'BarrelStrength\Sprout\redirects\RedirectsModule';
    public const REDIRECTS_TABLE = '{{%sprout_redirects}}';
    public const SETTINGS_TABLE = '{{%sprout_settings}}';

    public const EXACT_MATCH = 'exactMatch';
    public const REGEX_MATCH = 'regExMatch';
    public const URL_WITHOUT_QUERY_STRINGS = 'urlWithoutQueryStrings';
    public const REMOVE_QUERY_STRINGS = 'removeQueryStrings';

    /**
     * We only track the structureUid in the project config but can't push live the actual Structure
     * that gets created in the DB. So we need to make sure there is a structure in the DB that
     * matches the UID stored in the project config.
     */
    public function safeUp(): void
    {
        $moduleSettingsKey = self::SPROUT_KEY . '.' . self::MODULE_ID;
        $coreModuleSettingsKey = self::MODULES_KEY . '.' . self::MODULE_CLASS;

        // See if we have current settings
        $projectConfigSettings = Craft::$app->getProjectConfig()->get($moduleSettingsKey);
        $structureUidFromConfig = $projectConfigSettings['structureUid'] ?? null;

        $structureUidInDb = RedirectStructureHelper::getStructureUidFromDb($structureUidFromConfig);

        if (!$structureUidInDb) {
            throw new ElementNotFoundException('Unable to find or create Structure Element for Redirects.');
        }

        if (!$projectConfigSettings || !isset($projectConfigSettings['structureUid'])) {
            // Create Project Config settings in the repo
            Craft::$app->getProjectConfig()->set($moduleSettingsKey, [
                'matchDefinition' => self::URL_WITHOUT_QUERY_STRINGS,
                'queryStringStrategy' => self::REMOVE_QUERY_STRINGS,
                'enable404RedirectLog' => false,
                'trackRemoteIp' => false,
                'total404Redirects' => 250,
                'cleanupProbability' => 1000,
                'structureUid' => $structureUidInDb,
                'globallyExcludedUrlPatterns' => null,
            ], 'Update Sprout CP Settings for: ' . $moduleSettingsKey);

            Craft::$app->getProjectConfig()->set($coreModuleSettingsKey, [
                'enabled' => true,
            ]);
        }

        if ($structureUidFromConfig !== null) {
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
