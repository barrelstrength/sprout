<?php

/** @noinspection DuplicatedCode */

namespace BarrelStrength\Sprout\redirects\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Json;

class m211101_000001_migrate_settings_table_to_projectconfig extends Migration
{
    public const SPROUT_KEY = 'sprout';
    public const MODULES_KEY = self::SPROUT_KEY . '.sprout-module-core.modules';
    public const MODULE_ID = 'sprout-module-redirects';
    public const MODULE_CLASS = 'BarrelStrength\Sprout\redirects\RedirectsModule';
    public const OLD_SETTINGS_MODEL = 'barrelstrength\sproutbaseredirects\models\Settings';
    public const OLD_SETTINGS_TABLE = '{{%sprout_settings_craft3}}';

    public function safeUp(): void
    {
        $moduleSettingsKey = self::SPROUT_KEY . '.' . self::MODULE_ID;
        $coreModuleSettingsKey = self::MODULES_KEY . '.' . self::MODULE_CLASS;

        // Table renamed first in core migrations
        if (!$this->db->tableExists(self::OLD_SETTINGS_TABLE)) {
            return;
        }

        $oldSettings = (new Query())
            ->select([
                'model',
                'settings',
            ])
            ->from([self::OLD_SETTINGS_TABLE])
            ->where([
                'model' => self::OLD_SETTINGS_MODEL,
            ])
            ->one();

        if (empty($oldSettings)) {
            Craft::warning('No shared settings found to migrate: ' . self::MODULE_ID);

            return;
        }

        $currentProjectConfig = Craft::$app->getProjectConfig()->get($moduleSettingsKey) ?? [];

        // Prepare old settings for new settings format
        $newSettings = Json::decode($oldSettings['settings']);
        $newSettings = $this->prepareSettingsForMigration($newSettings, $currentProjectConfig);

        $newSettings = array_merge($currentProjectConfig, $newSettings);

        $newCoreSettings = [
            'enabled' => $newSettings['enableRedirects'] ?? true,
        ];

        unset(
            $newSettings['enableRedirects'],
            $newSettings['pluginNameOverride'],
        );

        Craft::$app->getProjectConfig()->set($moduleSettingsKey, $newSettings,
            "Update Sprout Settings for “{$moduleSettingsKey}”"
        );

        Craft::$app->getProjectConfig()->set($coreModuleSettingsKey, $newCoreSettings,
            "Update Sprout Core Settings for “{$coreModuleSettingsKey}”"
        );

        $this->delete(self::OLD_SETTINGS_TABLE, ['model' => self::OLD_SETTINGS_MODEL]);

        $this->deleteSettingsTableIfEmpty();
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }

    protected function prepareSettingsForMigration($newSettings, $currentProjectConfig): array
    {
        if (isset($newSettings['redirectMatchStrategy'])) {
            $newSettings['matchDefinition'] = $newSettings['redirectMatchStrategy']
                ?? 'urlWithoutQueryStrings';
        }

        if (!is_int($newSettings['total404Redirects'])) {
            $newSettings['total404Redirects'] = (int)$newSettings['total404Redirects'];
        }

        if (!is_int($newSettings['cleanupProbability'])) {
            $newSettings['cleanupProbability'] = (int)$newSettings['cleanupProbability'];
        }

        if ($newSettings['enable404RedirectLog'] === '1') {
            $newSettings['enable404RedirectLog'] = true;
        }

        if ($newSettings['enable404RedirectLog'] === '') {
            $newSettings['enable404RedirectLog'] = false;
        }

        if ($newSettings['trackRemoteIp'] === '1') {
            $newSettings['trackRemoteIp'] = true;
        }

        if ($newSettings['trackRemoteIp'] === '') {
            $newSettings['trackRemoteIp'] = false;
        }

        if (isset($newSettings['excludedUrlPatterns'])) {
            $newSettings['globallyExcludedUrlPatterns'] = $newSettings['excludedUrlPatterns'];
        }

        // Migrate structureId to structureUid
        if ($newSettings['structureId']) {

            // Find existing Structure UID
            $uid = (new Query)
                ->select('uid')
                ->from(Table::STRUCTURES)
                ->where([
                    'id' => (int)$newSettings['structureId'],
                ])
                ->scalar();

            // Update project config settings to reflect UID
            $newSettings['structureUid'] = $uid;

            // If the current project config UID does not match the existing UID
            // the current UID was created in the install migration before the
            // upgrade migrations found the existing ID and we can remove the
            // structure the database
            if ($currentProjectConfig['structureUid'] !== $uid) {
                $this->delete(Table::STRUCTURES, [
                    'uid' => $currentProjectConfig['structureUid'],
                ]);
            }
        }

        unset(
            $newSettings['structureId'],
            $newSettings['redirectMatchStrategy'],
            $newSettings['excludedUrlPatterns'],
        );

        return $newSettings;
    }

    public function deleteSettingsTableIfEmpty(): void
    {
        $oldSettings = (new Query())
            ->select('*')
            ->from([self::OLD_SETTINGS_TABLE])
            ->all();

        if (empty($oldSettings)) {
            $this->dropTableIfExists(self::OLD_SETTINGS_TABLE);
        }
    }
}
