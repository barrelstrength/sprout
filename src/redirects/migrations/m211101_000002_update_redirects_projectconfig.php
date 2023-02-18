<?php

namespace BarrelStrength\Sprout\redirects\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\errors\ElementNotFoundException;
use craft\records\Structure;

class m211101_000002_update_redirects_projectconfig extends Migration
{
    public const SPROUT_KEY = 'sprout';
    public const MODULE_ID = 'sprout-module-redirects';
    public const OLD_CONFIG_KEY = 'plugins.sprout-redirects.settings';
    public const URL_WITHOUT_QUERY_STRINGS = 'urlWithoutQueryStrings';
    public const REMOVE_QUERY_STRINGS = 'removeQueryStrings';
    public const SETTING_STRUCTURE_UID = 'structureUid';

    public function safeUp(): void
    {
        $moduleSettingsKey = self::SPROUT_KEY . '.' . self::MODULE_ID;

        $defaultSettings = [
            'cleanupProbability' => 1000,
            'enable404RedirectLog' => false,
            'excludedUrlPatterns' => null,
            'queryStringStrategy' => self::REMOVE_QUERY_STRINGS,
            'matchDefinition' => self::URL_WITHOUT_QUERY_STRINGS,
            'total404Redirects' => 250,
            'trackRemoteIp' => false,

            // This will be generated below if still empty after we merge settings
            'structureUid' => null,
        ];

        $oldConfig = Craft::$app->getProjectConfig()->get(self::OLD_CONFIG_KEY) ?? [];
        $newConfig = [];

        foreach ($defaultSettings as $key => $defaultValue) {
            $newConfig[$key] = isset($oldConfig[$key]) ? $oldConfig[$key] ?? $defaultValue : $defaultValue;
        }

        // Just in case
        if (empty($newConfig['structureId'])) {
            $newConfig['structureUid'] = $this->getStructureUid();
        } else {
            // Query DB for UID and update project config.
            $uid = (new Query)
                ->select('uid')
                ->from(Table::STRUCTURES)
                ->where([
                    'id' => (int)$newConfig['structureId'],
                ])
                ->scalar();

            $newConfig['structureUid'] = $uid;
        }

        unset($newConfig['structureId']);

        if (!is_int($newConfig['total404Redirects'])) {
            $newConfig['total404Redirects'] = (int)$newConfig['total404Redirects'];
        }

        if (!is_int($newConfig['cleanupProbability'])) {
            $newConfig['cleanupProbability'] = (int)$newConfig['cleanupProbability'];
        }

        if ($newConfig['enable404RedirectLog'] === '1') {
            $newConfig['enable404RedirectLog'] = true;
        }

        if ($newConfig['enable404RedirectLog'] === '') {
            $newConfig['enable404RedirectLog'] = false;
        }

        if ($newConfig['trackRemoteIp'] === '1') {
            $newConfig['trackRemoteIp'] = true;
        }

        if ($newConfig['trackRemoteIp'] === '') {
            $newConfig['trackRemoteIp'] = false;
        }

        // excludedUrlPatterns
        // Save to settings db...

        Craft::$app->getProjectConfig()->set($moduleSettingsKey, $newConfig,
            "Update Sprout Settings for “{$moduleSettingsKey}”"
        );

        Craft::$app->getProjectConfig()->remove(self::OLD_CONFIG_KEY);
    }

    public function safeDown(): bool
    {
        echo "m211101_000002_update_redirects_projectconfig cannot be reverted.\n";

        return false;
    }

    public function getStructureUid(): string
    {
        $structure = new Structure();
        $structure->maxLevels = 1;

        if (!$structure->save()) {
            throw new ElementNotFoundException('Unable to create Structure Element for Redirects.');
        }

        return $structure->uid;
    }
}
