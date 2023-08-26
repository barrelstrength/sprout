<?php

namespace BarrelStrength\Sprout\forms\formtypes;

use BarrelStrength\Sprout\forms\components\elements\FormElement;
use BarrelStrength\Sprout\forms\FormsModule;
use Craft;
use craft\base\SavableComponent;
use craft\models\FieldLayout;

abstract class FormType extends SavableComponent implements FormTypeInterface
{
    public ?string $name = null;

    public ?string $formTemplate = null;

    public ?string $formTemplateOverrideFolder = null;

    public ?string $submissionMethod = null;
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

    public function getFieldLayout(): FieldLayout
    {
        if ($this->_fieldLayout) {
            return $this->_fieldLayout;
        }

        $fieldLayout = new FieldLayout([
            'type' => FormElement::class,
        ]);

        return $this->_fieldLayout = $fieldLayout;
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
                    'name' => 'features[notifications][enabled]',
                    'on' => true,
                    'small' => true,
                ]),
                'heading' => 'Notifications',
            ],
            [
                'enabled' => Craft::$app->getView()->renderTemplate('_includes/forms/lightswitch.twig', [
                    'name' => 'features[reports][enabled]',
                    'on' => true,
                    'small' => true,
                ]),
                'heading' => 'Reports',
            ],
            [
                'enabled' => Craft::$app->getView()->renderTemplate('_includes/forms/lightswitch.twig', [
                    'name' => 'features[integrations][enabled]',
                    'on' => false,
                    'small' => true,
                ]),
                'heading' => 'Integrations',
            ],
        ];
    }

    public function getFormFieldRows(): array
    {
        $formFieldGroups = FormsModule::getInstance()->formFields->getDefaultFormFieldTypesByGroup();

        $rows = [];

        foreach ($formFieldGroups as $formFieldGroupKey => $formFields) {
            foreach ($formFields as $formFieldType) {
                $rows[] = [
                    'enabled' => Craft::$app->getView()->renderTemplate('_includes/forms/lightswitch.twig', [
                        'name' => 'formFields[' . $formFieldType . '][enabled]',
                        'on' => true,
                        'small' => true,
                    ]),
                    'heading' => $formFieldType::displayName(),
                    'group' => $formFieldGroupKey,
                ];
            }
        }

        return $rows;
    }

    public function getConfig(): array
    {
        $config = [
            'type' => static::class,
            'name' => $this->name,
            'formTemplate' => $this->formTemplate,
            'formTemplateOverrideFolder' => $this->formTemplateOverrideFolder,
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
