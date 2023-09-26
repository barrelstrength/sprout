<?php

/** @noinspection DuplicatedCode DuplicatedCode */

namespace BarrelStrength\Sprout\sitemaps\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

class m211101_000001_migrate_settings_table_to_projectconfig extends Migration
{
    public const SPROUT_KEY = 'sprout';
    public const MODULES_KEY = self::SPROUT_KEY . '.sprout-module-core.modules';
    public const MODULE_ID = 'sprout-module-sitemaps';
    public const MODULE_CLASS = 'BarrelStrength\Sprout\sitemaps\SitemapsModule';
    public const OLD_SETTINGS_CLASS = 'barrelstrength\sproutbasesitemaps\models\Settings';
    public const OLD_SETTINGS_TABLE = '{{%sprout_settings_craft3}}';

    public const AGGREGATION_METHOD_SINGLE_LANGUAGE = 'singleLanguageSitemaps';
    public const AGGREGATION_METHOD_MULTI_LINGUAL = 'multiLingualSitemaps';

    public function safeUp(): void
    {
        $moduleSettingsKey = self::SPROUT_KEY . '.' . self::MODULE_ID;
        $coreModuleSettingsKey = self::MODULES_KEY . '.' . self::MODULE_CLASS;

        if (!$this->db->tableExists(self::OLD_SETTINGS_TABLE)) {
            return;
        }

        // Get shared sprout settings from old schema
        $oldSettings = (new Query())
            ->select(['model', 'settings'])
            ->from([self::OLD_SETTINGS_TABLE])
            ->where([
                'model' => self::OLD_SETTINGS_CLASS,
            ])
            ->one();

        if (empty($oldSettings)) {
            Craft::warning('No shared settings found to migrate: ' . self::MODULE_ID);

            return;
        }

        // Prepare old settings for new settings format
        $newSettings = Json::decode($oldSettings['settings']);
        $newSettings = $this->prepareSettingsForMigration($newSettings);

        $newCoreSettings = [
            'enabled' => true,
        ];

        unset(
            $newSettings['pluginNameOverride'],
            $newSettings['enableMultilingualSitemaps'],
            $newSettings['enableDynamicSitemaps'],
            $newSettings['enableCustomSections'],
        );

        Craft::$app->getProjectConfig()->set($moduleSettingsKey, $newSettings,
            'Update Sprout Settings for “' . $moduleSettingsKey . '”'
        );

        Craft::$app->getProjectConfig()->set($coreModuleSettingsKey, $newCoreSettings,
            'Update Sprout Core Settings for “' . $coreModuleSettingsKey . '”'
        );

        $this->delete(self::OLD_SETTINGS_TABLE, ['model' => self::OLD_SETTINGS_CLASS]);

        $this->deleteSettingsTableIfEmpty();
    }

    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }

    public function prepareSettingsForMigration($newSettings): array
    {
        $primarySite = Craft::$app->getSites()->getPrimarySite();

        // Ensure proper data types
        if (!is_int($newSettings['totalElementsPerSitemap'])) {
            $newSettings['totalElementsPerSitemap'] = (int)$newSettings['totalElementsPerSitemap'];
        }

        if ($newSettings['enableCustomSections'] === '1') {
            $newSettings['enableCustomPagesSitemap'] = true;
        }

        if ($newSettings['enableCustomSections'] === '') {
            $newSettings['enableCustomPagesSitemap'] = false;
        }

        if ($newSettings['enableMultilingualSitemaps'] === '1') {
            $newSettings['sitemapAggregationMethod'] = self::AGGREGATION_METHOD_MULTI_LINGUAL;
        }

        if ($newSettings['enableMultilingualSitemaps'] === '') {
            $newSettings['sitemapAggregationMethod'] = self::AGGREGATION_METHOD_SINGLE_LANGUAGE;
        }

        if (isset($newSettings['groupSettings'])) {
            $newGroupSettings = [];
            foreach ($newSettings['groupSettings'] as $groupId => $value) {
                $group = Craft::$app->getSites()->getGroupById((int)$groupId);
                if ($group) {
                    $newGroupSettings[$group->uid] = (string)$value;
                }
            }
            $newSettings['groupSettings'] = $newGroupSettings;
        }

        if (isset($newSettings['siteSettings'])) {
            $newSiteSettings = [];
            foreach ($newSettings['siteSettings'] as $siteId => $value) {
                $site = Craft::$app->getSites()->getSiteById($siteId);
                if ($site) {
                    $newSiteSettings[$site->uid] = (string)$value;
                }
            }
            $newSettings['siteSettings'] = $newSiteSettings;
        }

        // Just in case
        if (empty($newSettings['siteSettings'])) {
            $newSettings['siteSettings'][$primarySite->uid] = '1';
        }

        if (empty($newSettings['groupSettings'])) {
            $newSettings['groupSettings'][$primarySite->getGroup()->uid] = '1';
        }

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
