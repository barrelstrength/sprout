<?php

namespace BarrelStrength\Sprout\forms\formtypes;

use BarrelStrength\Sprout\forms\components\elements\FormElement;
use BarrelStrength\Sprout\forms\FormsModule;
use Craft;
use craft\base\SavableComponent;
use craft\helpers\ArrayHelper;
use craft\models\FieldLayout;

abstract class FormType extends SavableComponent implements FormTypeInterface
{
    public ?string $name = null;

    public ?string $formTemplate = null;

    public ?string $formTemplateOverrideFolder = null;

    public bool $enableNotificationsTab = true;

    public bool $enableReportsTab = true;

    public bool $enableIntegrationsTab = true;

    public array $enabledFormFieldTypes = [];

    public ?string $submissionMethod = null;

    public ?string $errorDisplayMethod = null;

    public bool $enableSaveData = true;
    public bool $trackRemoteIp = false;
    public array $allowedAssetVolumes = [];

    public ?string $defaultUploadLocationSubpath = null;

    public bool $enableEditSubmissionViaFrontEnd = false;

    public ?FormElement $form = null;

    protected ?FieldLayout $_fieldLayout = null;

    public ?string $uid = null;

    public static function isEditable(): bool
    {
        return false;
    }

    /**
     * Adds pre-defined options for css classes.
     *
     * These classes will display in the CSS Classes dropdown list on the Field Edit modal
     * for Field Types that support the $cssClasses property.
     */
    public function getCssClassDefaults(): array
    {
        return [];
    }

    public function createFieldLayout(): ?FieldLayout
    {
        return null;
    }

    public function getFieldLayout(): ?FieldLayout
    {
        if ($this->_fieldLayout) {
            return $this->_fieldLayout;
        }

        $this->_fieldLayout = $this->createFieldLayout();

        return $this->_fieldLayout;
    }

    public function setFieldLayout(?FieldLayout $fieldLayout): void
    {
        $this->_fieldLayout = $fieldLayout;
    }

    public function getFeatureRows(): array
    {
        return [
            [
                'enabled' => Craft::$app->getView()->renderTemplate('_includes/forms/lightswitch.twig', [
                    'name' => 'enableNotificationsTab',
                    'on' => $this->enableNotificationsTab,
                    'small' => true,
                ]),
                'heading' => 'Notifications',
            ],
            [
                'enabled' => Craft::$app->getView()->renderTemplate('_includes/forms/lightswitch.twig', [
                    'name' => 'enableReportsTab',
                    'on' => $this->enableReportsTab,
                    'small' => true,
                ]),
                'heading' => 'Reports',
            ],
            [
                'enabled' => Craft::$app->getView()->renderTemplate('_includes/forms/lightswitch.twig', [
                    'name' => 'enableIntegrationsTab',
                    'on' => $this->enableIntegrationsTab,
                    'small' => true,
                ]),
                'heading' => 'Integrations',
            ],
        ];
    }

    public function getFormFieldTypesByType(): array
    {
        return array_combine($this->enabledFormFieldTypes, array_fill_keys($this->enabledFormFieldTypes, true));
    }

    public function getFormFieldFeatures(): array
    {
        $formFieldGroups = FormsModule::getInstance()->formFields->getDefaultFormFieldTypesByGroup();

        $options = [];

        foreach ($formFieldGroups as $formFieldGroupKey => $formFields) {
            foreach ($formFields as $formFieldType) {
                // add label/value keys to options
                $options[$formFieldGroupKey][$formFieldType] = $formFieldType::displayName();
            }
        }

        return $options ?? [];
    }

    public function getConfig(): array
    {
        $config = [
            'type' => static::class,
            'name' => $this->name,
            'formTemplate' => $this->formTemplate,
            'formTemplateOverrideFolder' => $this->formTemplateOverrideFolder,
            'enableNotificationsTab' => $this->enableNotificationsTab,
            'enableReportsTab' => $this->enableReportsTab,
            'enableIntegrationsTab' => $this->enableIntegrationsTab,
            'enabledFormFieldTypes' => $this->enabledFormFieldTypes,
        ];

        $fieldLayout = $this->getFieldLayout();

        if ($fieldLayoutConfig = $fieldLayout->getConfig()) {
            $config['fieldLayouts'] = [
                $fieldLayout->uid => $fieldLayoutConfig,
            ];
        }

        return $config;
    }
}
