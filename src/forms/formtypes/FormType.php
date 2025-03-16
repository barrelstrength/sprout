<?php

namespace BarrelStrength\Sprout\forms\formtypes;

use BarrelStrength\Sprout\forms\components\elements\FormElement;
use BarrelStrength\Sprout\forms\FormsModule;
use BarrelStrength\Sprout\mailer\emailtypes\EmailTypeHelper;
use BarrelStrength\Sprout\uris\links\LinkInterface;
use BarrelStrength\Sprout\uris\links\Links;
use Craft;
use craft\base\FieldLayoutProviderInterface;
use craft\base\SavableComponent;
use craft\models\FieldLayout;

abstract class FormType extends SavableComponent implements FormTypeInterface, FieldLayoutProviderInterface
{
    public function __construct($config = [])
    {
        //if (isset($config['redirectUrl'])) {
        //    $config['redirectUrl'] = Links::toLinkField($config['redirectUrl']) ?: null;
        //}

        //if (isset($config['submissionMethod']) || $config['submissionMethod'] === null) {
        unset($config['submissionMethod']);
        //}
        //if (isset($config['errorDisplayMethod']) || $config['errorDisplayMethod'] === null) {
        unset($config['errorDisplayMethod']);
        //}

        parent::__construct($config);
    }

    public function setAttributes($values, $safeOnly = true): void
    {
        // @todo - How to clean this up and use across form types?
        $redirectLinkData = $values['redirectUrl'] ?? null;

        if (!$redirectLinkData instanceof LinkInterface) {
            $link = RedirectUrlField::getNewRedirectLinkField();
            $values['redirectUrl'] = $link->normalizeValueFromRequest($redirectLinkData, null);
        }

        // reindex keys to allow re-ordering
        $this->formTypeMetadata = array_values($this->formTypeMetadata);

        parent::setAttributes($values, $safeOnly);
    }

    //  General
    public ?string $name = null;

    public ?string $handle = null;

    public ?string $customTemplatesFolder = null;

    // Features
    public array $featureSettings = [];

    public array $enabledFormFieldTypes = [];

    // Behavior
    public bool $enableSaveData = true;

    public bool $enableEditSubmissionViaFrontEnd = false;

    public array|string $allowedAssetVolumes = [];

    public ?string $defaultUploadLocationSubpath = null;

    public array $formTypeMetadata = [];

    // Native Fields
    public LinkData|array|null $redirectUrl = null;

    public bool $enableCaptchas = false;

    public ?FormElement $form = null;

    protected ?FieldLayout $_fieldLayout = null;

    public ?string $uid = null;

    public function getIncludeTemplates(): array
    {
        return [
            Craft::getAlias($this->getCustomTemplatesFolder()),
            $this->getRenderTemplatesFolder(),
            Craft::getAlias($this->getDefaultTemplatesFolder()),
        ];
    }

    public function getCustomTemplatesFolder(): ?string
    {
        return $this->customTemplatesFolder;
    }

    public function getRenderTemplatesFolder(): ?string
    {
        $generalConfig = Craft::$app->getConfig()->getGeneral();

        return $generalConfig->partialTemplatesPath . DIRECTORY_SEPARATOR . FormElement::refHandle() . DIRECTORY_SEPARATOR . $this->handle;
    }

    public function getDefaultTemplatesFolder(): ?string
    {
        return null;
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

    public function getFieldLayout(): FieldLayout
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

    public function getFormFieldTypesByType(): array
    {
        if (empty($this->enabledFormFieldTypes)) {
            // Default to all
            return FormsModule::getInstance()->formFields->getFormFieldTypes();
        }

        return array_combine($this->enabledFormFieldTypes, array_fill_keys($this->enabledFormFieldTypes, true));
    }

    public function getEmailTypesOptions(): array
    {
        return EmailTypeHelper::getEmailTypesOptions();
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

        return $options;
    }

    public function getSettings(): array
    {
        $settings = parent::getSettings();

        foreach ($settings as $key => $value) {
            if ($key === 'redirectUrl' && $value !== null) {
                $settings['redirectUrl'] = $value->serialize();
            }
        }

        return $settings;
    }

    public function getRedirectUrl(): ?string
    {
        return $this->redirectUrl?->getValue();
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['name', 'handle'], 'required'];

        return $rules;
    }

    public function getConfig(): array
    {
        ;
        $config = [
            'type' => static::class,
            'name' => $this->name,
            'handle' => $this->handle,
            'customTemplatesFolder' => $this->customTemplatesFolder,
            'featureSettings' => $this->featureSettings,
            'enabledFormFieldTypes' => $this->enabledFormFieldTypes,
            'enableSaveData' => $this->enableSaveData,
            'enableEditSubmissionViaFrontEnd' => $this->enableEditSubmissionViaFrontEnd,
            'allowedAssetVolumes' => $this->allowedAssetVolumes,
            'defaultUploadLocationSubpath' => $this->defaultUploadLocationSubpath,
            'formTypeMetadata' => $this->formTypeMetadata,
            'customSettings' => $this->getSettings(),
        ];

        $fieldLayout = $this->getFieldLayout();

        if ($fieldLayoutConfig = $fieldLayout->getConfig()) {
            $config['fieldLayouts'] = [
                $fieldLayout->uid => $fieldLayoutConfig,
            ];
        }

        return $config;
    }

    public static function defaultCardAttributes(): array
    {
        return [];
    }
}
