<?php

namespace BarrelStrength\Sprout\forms\formtypes;

use BarrelStrength\Sprout\forms\components\formtypes\DefaultFormType;
use BarrelStrength\Sprout\forms\FormsModule;
use Craft;
use craft\errors\MissingComponentException;
use craft\events\DefineFieldLayoutFieldsEvent;
use craft\helpers\ProjectConfig;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;

class FormTypeHelper
{
    public static function defineNativeFieldsPerFormType(DefineFieldLayoutFieldsEvent $event): void
    {
        /** @var FieldLayout $fieldLayout */
        $fieldLayout = $event->sender;

        $formTypeTypes = FormsModule::getInstance()->formTypes->getFormTypeTypes();

        foreach ($formTypeTypes as $formTypeType) {
            if ($fieldLayout->type === $formTypeType) {
                $formTypeType::defineNativeFields($event);
            }
        }
    }

    public static function getFormTypes(): array
    {
        $settings = FormsModule::getInstance()->getSettings();

        $formTypesConfig = ProjectConfig::unpackAssociativeArray($settings->formTypes);

        foreach ($formTypesConfig as $uid => $config) {
            $formTypes[$uid] = self::getFormTypeModel($config, $uid);
        }

        return $formTypes ?? [];
    }

    public static function saveFormTypes(array $formTypes): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $configPath = FormsModule::projectConfigPath('formTypes');
        $formTypeConfigs = [];

        foreach ($formTypes as $uid => $formType) {
            $formTypeConfigs[$uid] = $formType->getConfig();
        }

        if (!$projectConfig->set($configPath, ProjectConfig::packAssociativeArray($formTypeConfigs))) {
            return false;
        }

        return true;
    }

    public static function removeFormType(string $uid): bool
    {
        $formTypes = self::getFormTypes();

        unset($formTypes[$uid]);

        if (!self::saveFormTypes($formTypes)) {
            return false;
        }

        return true;
    }

    public static function getFormTypeByUid(string $uid): ?FormType
    {
        $formTypes = self::getFormTypes();

        return $formTypes[$uid] ?? null;
    }

    public static function getFormTypeModel(array $formTypeSettings, string $uid = null): ?FormType
    {
        $type = $formTypeSettings['type'];

        $formType = new $type([
            'name' => $formTypeSettings['name'] ?? null,
            'formTemplate' => $formTypeSettings['formTemplate'] ?? null,
            'formTemplateOverrideFolder' => $formTypeSettings['formTemplateOverrideFolder'] ?? null,
            'featureSettings' => $formTypeSettings['featureSettings'] ?? null,
            'enabledFormFieldTypes' => $formTypeSettings['enabledFormFieldTypes'] ?? null,
            'uid' => $uid ?? StringHelper::UUID(),
        ]);

        if (isset($formTypeSettings['fieldLayouts'])) {
            $config = reset($formTypeSettings['fieldLayouts']);
            $config['type'] = $type;

            $fieldLayout = FieldLayout::createFromConfig($config);

            $formType->setFieldLayout($fieldLayout);
        }

        return $formType;
    }

    public static function reorderFormTypes(array $uids = []): bool
    {
        $oldFormTypes = self::getFormTypes();
        $newFormTypes = [];

        foreach ($uids as $uid) {
            $newFormTypes[$uid] = $oldFormTypes[$uid];
        }

        if (!self::saveFormTypes($newFormTypes)) {
            return false;
        }

        return true;
    }

    public static function getDefaultFormType()
    {
        $formTypes = self::getFormTypes();

        if (!$defaultFormType = reset($formTypes)) {
            self::createDefaultFormType();

            return self::getDefaultFormType();
        }

        return $defaultFormType;
    }

    public static function createDefaultFormType(): void
    {
        $formType = new DefaultFormType();
        $formType->uid = StringHelper::UUID();

        if (!$formType->uid) {
            $formType->uid = StringHelper::UUID();
        }

        $formTypesConfig = self::getFormTypes();
        $formTypesConfig[$formType->uid] = $formType;

        if (!$formType->validate() || !self::saveFormTypes($formTypesConfig)) {
            throw new MissingComponentException('Unable to create default form type.');
        }
    }
}
