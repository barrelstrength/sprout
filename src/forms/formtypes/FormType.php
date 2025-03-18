<?php

namespace BarrelStrength\Sprout\forms\formtypes;

use BarrelStrength\Sprout\forms\components\elements\FormElement;
use BarrelStrength\Sprout\forms\FormsModule;
use BarrelStrength\Sprout\mailer\emailtypes\EmailTypeHelper;
use Craft;
use craft\base\FieldLayoutProviderInterface;
use craft\base\SavableComponent;
use craft\fields\data\LinkData;
use craft\models\FieldLayout;

abstract class FormType extends SavableComponent implements FormTypeInterface, FieldLayoutProviderInterface
{
    public function setAttributes($values, $safeOnly = true): void
    {
        if (array_key_exists('redirectUrl', $values) && is_array($values['redirectUrl'])) {
            $values['redirectUrl'] = LinkFieldLayoutFieldHelper::toFieldLayoutField($values['redirectUrl']);
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

    public array|string $allowedAssetVolumes = '*';

    public ?string $defaultUploadLocationSubpath = null;

    public array $formTypeMetadata = [];

    // Native Fields
    public LinkData|array|null $redirectUrl = null;

    public bool $enableCaptchas = false;

    // Misc
    public ?FormElement $form = null;

    protected ?FieldLayout $_fieldLayout = null;

    public ?string $uid = null;

    /**
     * Most of the above form type settings are handled by saving the form type in the project config
     * Some settings, need to be stored on the Form Elements and those attributes should be included here.
     *
     * @todo can redirect URL be included as a trait?
     */
    public function settingsAttributes(): array
    {
        $attributes = parent::settingsAttributes();

        return array_merge($attributes, [
            'redirectUrl',
        ]);
    }

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
        return $this->redirectUrl?->getUrl();
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['name', 'handle'], 'required'];
        $rules[] = ['redirectUrl', 'validateRedirectUrl'];

        return $rules;
    }

    public function validateRedirectUrl($attribute, $params): void
    {
        if ($this->redirectUrl) {
            $linkType = $this->redirectUrl->getType();
            $value = $this->redirectUrl->getValue();

            $linkTypeClass = LinkFieldLayoutFieldHelper::getLinkTypeClassById($linkType);
            $link = new $linkTypeClass();

            if (!$link->validateValue($value)) {
                $this->addError($attribute, Craft::t('sprout-module-forms', 'Invalid redirect URL'));
            }
        }
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
