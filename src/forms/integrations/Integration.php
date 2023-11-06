<?php

namespace BarrelStrength\Sprout\forms\integrations;

use BarrelStrength\Sprout\forms\components\elements\FormElement;
use BarrelStrength\Sprout\forms\components\formfields\NumberFormField;
use BarrelStrength\Sprout\forms\components\formfields\SingleLineFormField;
use BarrelStrength\Sprout\forms\formfields\FormField;
use BarrelStrength\Sprout\forms\FormsModule;
use Craft;
use craft\base\SavableComponent;
use craft\fields\Date as CraftDate;
use craft\fields\Dropdown as CraftDropdown;
use craft\fields\Number as CraftNumber;
use craft\fields\PlainText as CraftPlainText;

abstract class Integration extends SavableComponent implements IntegrationInterface
{
    use IntegrationTrait;

    protected ?string $successMessage = null;

    protected ?array $sourceFormFieldsFromPage = null;

    public function __construct($config = [])
    {
        if (isset($config['sourceFormFieldsFromPage'])) {
            unset($config['sourceFormFieldsFromPage']);
        }

        parent::__construct($config);
    }

    /**
     */
    public function init(): void
    {
        parent::init();

        /**
         * Make sure we have a formId, if not, we're just instantiating a
         *    generic element and should add it shortly. We need the Form ID
         *    to properly prepare the fieldMapping.
         */

        if ($this->formId) {
            $this->refreshFieldMapping();
        }
    }

    public function getForm(): FormElement
    {
        if (!$this->form) {
            $this->form = FormsModule::getInstance()->forms->getFormById($this->formId);
        }

        return $this->form;
    }

    public function settingsAttributes(): array
    {
        $attributes = parent::settingsAttributes();
        $attributes[] = 'fieldMapping';

        return $attributes;
    }

    public function getSuccessMessage(): ?string
    {
        return $this->successMessage ?? Craft::t('sprout-module-forms', 'Success');
    }

    public function submit(): bool
    {
        return false;
    }

    /**
     * Returns a list of Source Form Fields as Field Instances
     *
     * Field Instances will be used to help create the fieldMapping using field handles.
     *
     */
    public function getSourceFormFields(): array
    {
        $sourceFormFieldsData = $this->getDefaultSourceMappingAttributes();

        $sourceFormFields = [];

        foreach ($sourceFormFieldsData as $sourceFormFieldData) {
            /** @var FormField $fieldInstance */
            $fieldInstance = new $sourceFormFieldData['type']();
            $fieldInstance->name = $sourceFormFieldData['name'];
            $fieldInstance->handle = $sourceFormFieldData['handle'];
            $fieldInstance->setCompatibleCraftFields($sourceFormFieldData['compatibleCraftFields']);
            $sourceFormFields[] = $fieldInstance;
        }

        // @todo - move custom field logic to be handled by js
        //$fields = $this->getForm()->getFields();
        //
        //foreach ($fields as $field) {
        //    $sourceFormFields[] = $field;
        //}

        return $sourceFormFields;
    }

    /**
     * Prepares a list of the Form Fields from the current form that a user can choose
     * to map to an endpoint. Fields are returned in a Select dropdown compatible format.
     *
     *
     */
    public function getSourceFormFieldsAsMappingOptions(bool $addOptGroup = false): array
    {
        $options = [];

        if ($addOptGroup) {
            $options[] = ['optgroup' => Craft::t('sprout-module-forms', 'Default Fields')];
        }

        $options = array_merge($options, [
            [
                'label' => Craft::t('sprout-module-forms', 'Form ID'),
                'value' => 'formId',
                'compatibleCraftFields' => [
                    CraftPlainText::class,
                    CraftDropdown::class,
                    CraftNumber::class,
                ],
                'fieldType' => SingleLineFormField::class,
            ],
            [
                'label' => Craft::t('sprout-module-forms', 'Submission ID'),
                'value' => 'id',
                'compatibleCraftFields' => [
                    CraftPlainText::class,
                    CraftDropdown::class,
                    CraftNumber::class,
                ],
                'fieldType' => SingleLineFormField::class,
            ],
            [
                'label' => Craft::t('sprout-module-forms', 'Title'),
                'value' => 'title',
                'compatibleCraftFields' => [
                    CraftPlainText::class,
                    CraftDropdown::class,
                ],
                'fieldType' => SingleLineFormField::class,
            ],
            [
                'label' => Craft::t('sprout-module-forms', 'Date Created'),
                'value' => 'dateCreated',
                'compatibleCraftFields' => [
                    CraftDate::class,
                ],
                'fieldType' => SingleLineFormField::class,
            ],
            [
                'label' => Craft::t('sprout-module-forms', 'IP Address'),
                'value' => 'ipAddress',
                'compatibleCraftFields' => [
                    CraftPlainText::class,
                    CraftDropdown::class,
                ],
                'fieldType' => SingleLineFormField::class,
            ],
            [
                'label' => Craft::t('sprout-module-forms', 'User Agent'),
                'value' => 'userAgent',
                'compatibleCraftFields' => [
                    CraftPlainText::class,
                ],
                'fieldType' => SingleLineFormField::class,
            ],
        ]);

        // @todo - move custom field logic to be handled by js
        //$fieldElements = $this->getForm()->getFieldLayout()?->getCustomFieldElements();
        //
        //if ($fieldElements !== []) {
        //    if ($addOptGroup) {
        //        $options[] = [
        //            'optgroup' => Craft::t('sprout-module-forms', 'Custom Fields'),
        //        ];
        //    }
        //
        //    foreach ($fieldElements as $fieldElement) {
        //        $field = $fieldElement->getField();
        //        $options[] = [
        //            'label' => $field->name,
        //            'value' => $field->handle,
        //            'compatibleCraftFields' => $field->getCompatibleCraftFieldTypes(),
        //            'fieldType' => $field::class,
        //        ];
        //    }
        //}

        return $options;
    }

    public function getTargetIntegrationFieldsAsMappingOptions(): array
    {
        return [];
    }

    /**
     * Represents a Field Mapping as a one-dimensional array where the
     * key is the sourceFormFieldHandle and the value is the targetIntegrationField handle
     *
     * [
     *   'title' => 'title',
     *   'customFormFieldHandle' => 'customTargetFieldHandle'
     * ]
     */
    public function getIndexedFieldMapping(): array
    {
        if ($this->fieldMapping === null) {
            return [];
        }

        $indexedFieldMapping = [];

        // Update our stored settings to use the sourceFormField handle as the key of our array
        foreach ($this->fieldMapping as $fieldMap) {
            $indexedFieldMapping[$fieldMap['sourceFormField']] = $fieldMap['targetIntegrationField'];
        }

        return $indexedFieldMapping;
    }

    /**
     * Updates the Field Mapping with any fields that have been added
     * to the Field Layout for a given form
     */
    public function refreshFieldMapping(): void
    {
        $newFieldMapping = [];
        $sourceFormFields = $this->getSourceFormFields();
        $indexedFieldMapping = $this->getIndexedFieldMapping();

        // Loop through the current list of form fields and match them to any existing fieldMapping settings
        foreach ($sourceFormFields as $sourceFormField) {
            // If the handle exists in our old field mapping (a field that was just
            // added to the form may not exist yet) use that value. Default to empty string.
            $targetIntegrationField = $indexedFieldMapping[$sourceFormField->handle] ?? '';

            $newFieldMapping[] = [
                'sourceFormField' => $sourceFormField->handle,
                'targetIntegrationField' => $targetIntegrationField,
            ];
        }

        $this->fieldMapping = $newFieldMapping;
    }

    public function getTargetIntegrationFieldValues(): array
    {
        if (!$this->fieldMapping) {
            return [];
        }

        $fields = [];
        $submission = $this->submission;

        foreach ($this->fieldMapping as $fieldMap) {
            if (isset($submission->{$fieldMap['sourceFormField']}) && $fieldMap['targetIntegrationField']) {
                $fields[$fieldMap['targetIntegrationField']] = $submission->{$fieldMap['sourceFormField']};
            }
        }

        return $fields;
    }

    /**
     * Returns the HTML where a user will prepare a field mapping
     */
    public function getFieldMappingSettingsHtml(): ?string
    {
        return null;
    }

    // @todo - update to use Condition Builders and FormBuilder.js, etc
    //final public function getSendRuleOptions(): array
    //{
    //    $fields = $this->getForm()->getFields();
    //    $optIns = [];
    //    $fieldHandles = [];
    //    //
    //    foreach ($fields as $field) {
    //        if ($field::class == OptInFormField::class) {
    //            $optIns[] = [
    //                'label' => $field->name . ' (' . $field->handle . ')',
    //                'value' => $field->handle,
    //            ];
    //            $fieldHandles[] = $field->handle;
    //        }
    //    }
    //
    //    $options = [
    //        [
    //            'label' => Craft::t('sprout-module-forms', 'Always'),
    //            'value' => '*',
    //        ],
    //    ];
    //
    //    $options = [...$options, ...$optIns];
    //
    //    $customSendRule = $this->sendRule;
    //
    //    $options[] = [
    //        'optgroup' => Craft::t('sprout-module-forms', 'Custom Rule'),
    //    ];
    //
    //    if (!in_array($this->sendRule, $fieldHandles, false) && $customSendRule != '*') {
    //        $options[] = [
    //            'label' => $customSendRule,
    //            'value' => $customSendRule,
    //        ];
    //    }
    //
    //    $options[] = [
    //        'label' => Craft::t('sprout-module-forms', 'Add Custom'),
    //        'value' => 'custom',
    //    ];
    //
    //    return $options;
    //}

    protected function getDefaultSourceMappingAttributes(): array
    {
        return [
            [
                'name' => Craft::t('sprout-module-forms', 'Form ID'),
                'handle' => 'formId',
                'compatibleCraftFields' => [
                    CraftPlainText::class,
                    CraftDropdown::class,
                    CraftNumber::class,
                ],
                'type' => NumberFormField::class,
            ],
            [
                'name' => Craft::t('sprout-module-forms', 'Submission ID'),
                'handle' => 'id',
                'compatibleCraftFields' => [
                    CraftPlainText::class,
                    CraftDropdown::class,
                    CraftNumber::class,
                ],
                'type' => NumberFormField::class,
            ],
            [
                'name' => Craft::t('sprout-module-forms', 'Title'),
                'handle' => 'title',
                'compatibleCraftFields' => [
                    CraftPlainText::class,
                    CraftDropdown::class,
                ],
                'type' => SingleLineFormField::class,
            ],
            [
                'name' => Craft::t('sprout-module-forms', 'Date Created'),
                'handle' => 'dateCreated',
                'compatibleCraftFields' => [
                    CraftDate::class,
                ],
                'type' => SingleLineFormField::class,
            ],
            [
                'name' => Craft::t('sprout-module-forms', 'IP Address'),
                'handle' => 'ipAddress',
                'compatibleCraftFields' => [
                    CraftPlainText::class,
                    CraftDropdown::class,
                ],
                'type' => SingleLineFormField::class,
            ],
            [
                'name' => Craft::t('sprout-module-forms', 'User Agent'),
                'handle' => 'userAgent',
                'compatibleCraftFields' => [
                    CraftPlainText::class,
                ],
                'type' => SingleLineFormField::class,
            ],
        ];
    }

    public function getConfig(): array
    {
        $config = [
            'type' => static::class,
            'name' => $this->name,
            'settings' => $this->getSettings(),
        ];

        return $config;
    }
}
