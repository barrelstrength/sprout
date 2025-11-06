<?php

namespace BarrelStrength\Sprout\forms\components\formfields;

use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use BarrelStrength\Sprout\forms\formfields\FormFieldInterface;
use BarrelStrength\Sprout\forms\formfields\FormFieldTrait;
use BarrelStrength\Sprout\forms\formfields\GroupLabel;
use BarrelStrength\Sprout\forms\FormsModule;
use Craft;
use craft\fields\Assets as CraftAssets;

class FileUploadFormField extends CraftAssets implements FormFieldInterface
{
    use FormFieldTrait;

    /**
     * Override the CP default for front-end use.
     */
    public bool $restrictLocation = true;

    protected string $settingsTemplate = 'sprout-module-forms/_components/fields/FileUpload/settings';

    public function __construct(array $config = [])
    {
        parent::__construct($config);

        if (!$this->defaultUploadLocationSubpath) {
            $this->defaultUploadLocationSubpath = $this->formType->defaultUploadLocationSubpath;
        }

        if (!$this->sources) {
            $this->allowedAssetVolumes = $this->formType->allowedAssetVolumes;
        }
    }

    public static function getGroupLabel(): string
    {
        return GroupLabel::label(GroupLabel::GROUP_COMMON);
    }

    public static function displayName(): string
    {
        return Craft::t('sprout-module-forms', 'File Upload');
    }

    public static function defaultSelectionLabel(): string
    {
        return Craft::t('sprout-module-forms', 'Add a file');
    }

    /**
     * Make these attributes available as Form Field settings
     */
    public function settingsAttributes(): array
    {
        $attributes = parent::settingsAttributes();
        $attributes[] = 'allowedKinds';
        $attributes[] = 'defaultUploadLocationSource';
        $attributes[] = 'defaultUploadLocationSubpath';
        $attributes[] = 'restrictedLocationSource';
        $attributes[] = 'restrictedLocationSubpath';
        $attributes[] = 'restrictFiles';
        $attributes[] = 'allowedKinds';

        return $attributes;
    }

    protected function settingsTemplateVariables(): array
    {
        $variables = parent::settingsTemplateVariables();

        $allowedSourceOptions = $this->getSourceOptions();

        if ($this->formType->allowedAssetVolumes !== '*') {
            foreach ($allowedSourceOptions as $key => $sourceOption) {
                if (!in_array($sourceOption['value'], $this->formType->allowedAssetVolumes, false)) {
                    unset($allowedSourceOptions[$key]);
                }
            }
        }

        $variables['allowedSourceOptions'] = $allowedSourceOptions;

        return $variables;
    }

    public function selectorIcon(): string
    {
        return 'cloud-arrow-up';
    }

    public function getFieldInputFolder(): string
    {
        return 'fileupload';
    }

    public function getExampleInputHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/fields/FileUpload/example',
            [
                'field' => $this,
            ]
        );
    }

    public function getFrontEndInputVariables($value, SubmissionElement $submission, array $renderingOptions = null): array
    {
        $multiple = $this->maxRelations === null || $this->maxRelations > 1;

        return [
            'name' => $this->handle,
            'value' => $submission->getFrontEndFormFieldValue($this),
            'renderingOptions' => $renderingOptions,
            'multiple' => $multiple,
        ];
    }

    public function getCompatibleCraftFieldTypes(): array
    {
        return [
            CraftAssets::class,
        ];
    }
}
