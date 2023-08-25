<?php

namespace BarrelStrength\Sprout\mailer\emailtypes;

use BarrelStrength\Sprout\mailer\MailerModule;
use Craft;
use craft\events\DefineFieldLayoutFieldsEvent;
use craft\helpers\ProjectConfig;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;

class EmailTypeHelper
{
    public static function defineNativeFieldsPerEmailType(DefineFieldLayoutFieldsEvent $event): void
    {
        /** @var FieldLayout $fieldLayout */
        $fieldLayout = $event->sender;

        $emailTypeTypes = MailerModule::getInstance()->emailTypes->getEmailTypeTypes();

        foreach ($emailTypeTypes as $emailTypeType) {
            if ($fieldLayout->type === $emailTypeType) {
                $emailTypeType::defineNativeFields($event);
            }
        }
    }

    public static function getEmailTypes(): array
    {
        $settings = MailerModule::getInstance()->getSettings();

        $emailTypesConfig = ProjectConfig::unpackAssociativeArray($settings->emailTypes);

        foreach ($emailTypesConfig as $uid => $config) {
            $emailTypes[$uid] = self::getEmailTypeModel($config, $uid);
        }

        return $emailTypes ?? [];
    }

    public static function saveEmailTypes(array $emailTypes): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $configPath = MailerModule::projectConfigPath('emailTypes');

        $emailTypeConfigs = [];

        foreach ($emailTypes as $uid => $emailType) {
            $emailTypeConfigs[$uid] = $emailType->getConfig();
        }

        if (!$projectConfig->set($configPath, ProjectConfig::packAssociativeArray($emailTypeConfigs))) {
            return false;
        }

        return true;
    }

    public static function removeEmailType(string $uid): bool
    {
        $emailTypes = self::getEmailTypes();

        unset($emailTypes[$uid]);

        if (!self::saveEmailTypes($emailTypes)) {
            return false;
        }

        return true;
    }

    public static function reorderEmailTypes(array $uids = []): bool
    {
        $oldEmailTypes = self::getEmailTypes();
        $newEmailTypes = [];

        foreach ($uids as $uid) {
            $newEmailTypes[$uid] = $oldEmailTypes[$uid];
        }

        if (!self::saveEmailTypes($newEmailTypes)) {
            return false;
        }

        return true;
    }

    public static function getEmailTypeByUid(string $uid): ?EmailType
    {
        $emailTypes = self::getEmailTypes();

        return $emailTypes[$uid] ?? null;
    }

    public static function getEmailTypeModel(array $emailTypeConfig, string $uid = null): ?EmailType
    {
        $type = $emailTypeConfig['type'];

        $config = reset($emailTypeConfig['fieldLayouts']);
        $config['type'] = $type;

        $fieldLayout = FieldLayout::createFromConfig($config);

        $emailType = new $type([
            'name' => $emailTypeConfig['name'],
            'displayPreheaderText' => $emailTypeConfig['displayPreheaderText'] ?? false,
            'htmlEmailTemplate' => $emailTypeConfig['htmlEmailTemplate'] ?? null,
            'textEmailTemplate' => $emailTypeConfig['textEmailTemplate'] ?? null,
            'copyPasteEmailTemplate' => $emailTypeConfig['copyPasteEmailTemplate'] ?? null,
            'uid' => $uid ?? StringHelper::UUID(),
        ]);

        $emailType->setFieldLayout($fieldLayout);

        return $emailType;
    }

    public static function getDefaultEmailType()
    {
        $emailTypes = self::getEmailTypes();

        return reset($emailTypes);
    }
}
