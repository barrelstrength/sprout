<?php

namespace BarrelStrength\Sprout\forms\integrations;

use BarrelStrength\Sprout\forms\FormsModule;
use Craft;
use craft\helpers\ProjectConfig;
use craft\helpers\StringHelper;

class IntegrationTypeHelper
{
    public static function getIntegrationTypes(): array
    {
        $settings = FormsModule::getInstance()->getSettings();

        $integrationTypesConfig = ProjectConfig::unpackAssociativeArray($settings->integrationTypes);

        foreach ($integrationTypesConfig as $uid => $config) {
            $integrationTypes[$uid] = self::getIntegrationTypeModel($config, $uid);
        }

        return $integrationTypes ?? [];
    }

    public static function saveIntegrationTypes(array $integrationTypes): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $configPath = FormsModule::projectConfigPath('integrationTypes');
        $integrationTypeConfigs = [];

        foreach ($integrationTypes as $uid => $integrationType) {
            $integrationTypeConfigs[$uid] = $integrationType->getConfig();
        }

        if (!$projectConfig->set($configPath, ProjectConfig::packAssociativeArray($integrationTypeConfigs))) {
            return false;
        }

        return true;
    }

    public static function removeIntegrationType(string $uid): bool
    {
        $integrationTypes = self::getIntegrationTypes();

        unset($integrationTypes[$uid]);

        if (!self::saveIntegrationTypes($integrationTypes)) {
            return false;
        }

        return true;
    }

    public static function getIntegrationTypeByUid(string $uid): ?Integration
    {
        $integrationTypes = self::getIntegrationTypes();

        return $integrationTypes[$uid] ?? null;
    }

    public static function getIntegrationTypeModel(array $integrationTypeSettings, string $uid = null): ?Integration
    {
        $type = $integrationTypeSettings['type'];

        $integrationType = new $type([
            'name' => $integrationTypeSettings['name'] ?? null,
            'uid' => $uid ?? StringHelper::UUID(),
        ]);

        if (isset($integrationTypeSettings['settings'])) {
            $integrationType->setAttributes($integrationTypeSettings['settings'], false);
        }

        return $integrationType;
    }

    public static function reorderIntegrationTypes(array $uids = []): bool
    {
        $oldIntegrationTypes = self::getIntegrationTypes();
        $newIntegrationTypes = [];

        foreach ($uids as $uid) {
            $newIntegrationTypes[$uid] = $oldIntegrationTypes[$uid];
        }

        if (!self::saveIntegrationTypes($newIntegrationTypes)) {
            return false;
        }

        return true;
    }
}
