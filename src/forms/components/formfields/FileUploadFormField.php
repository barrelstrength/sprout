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

    public string $cssClasses = '';

    /**
     * Override the CP default for front-end use.
     */
    public bool $restrictLocation = true;

    protected string $settingsTemplate = 'sprout-module-forms/_components/fields/FileUpload/settings';

    public function __construct(array $config = [])
    {
        parent::__construct($config);

        // @todo - can we do this somewhere else? Fields get loaded by Craft and this
        // enables the form module when it doesn't need to be enabled.
        if (FormsModule::isEnabled() && !$this->defaultUploadLocationSubpath) {
            $settings = FormsModule::getInstance()->getSettings();
            $this->defaultUploadLocationSubpath = $settings->defaultUploadLocationSubpath;
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

        $settings = FormsModule::getInstance()->getSettings();

        $allowedSourceOptions = $this->getSourceOptions();

        if ($settings->allowedAssetVolumes !== '*') {
            foreach ($allowedSourceOptions as $key => $sourceOption) {
                if (!in_array($sourceOption['value'], $settings->allowedAssetVolumes, false)) {
                    unset($allowedSourceOptions[$key]);
                }
            }
        }

        $variables['allowedSourceOptions'] = $allowedSourceOptions;

        return $variables;
    }

    public function getSvgIconPath(): string
    {
        return '@Sprout/Assets/dist/static/fields/icons/cloud-upload.svg';
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
            'value' => $value,
            //'field' => $this,
            //'submission' => $submission,
            'renderingOptions' => $renderingOptions,
            'multiple' => $multiple,
        ];
    }

    //public function getFrontEndInputHtml($value, SubmissionElement $submission, array $renderingOptions = null): Markup
    //{
    //    $rendered = Craft::$app->getView()->renderTemplate('fileupload/input',
    //        [
    //            'name' => $this->handle,
    //            'value' => $value,
    //            'field' => $this,
    //            'submission' => $submission,
    //            'renderingOptions' => $renderingOptions,
    //        ]
    //    );
    //
    //    return TemplateHelper::raw($rendered);
    //}

    public function getCompatibleCraftFieldTypes(): array
    {
        return [
            CraftAssets::class,
        ];
    }
}
