<?php

namespace BarrelStrength\Sprout\forms\components\integrationtypes;

use BarrelStrength\Sprout\forms\integrations\ElementIntegration;
use Craft;
use craft\elements\Entry;
use craft\elements\User;
use craft\fields\Date;
use craft\fields\PlainText;
use craft\models\EntryType;
use yii\web\IdentityInterface;

class EntryElementIntegrationType extends ElementIntegration
{
    /**
     * The Entry Type ID where the Form Field values will be mapped to
     */
    public ?int $entryTypeId = null;

    public static function displayName(): string
    {
        return Craft::t('sprout-module-forms', 'Entry Element (Craft)');
    }

    public function getSettingsHtml(): ?string
    {
        $sections = $this->getSectionsAsOptions();

        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/integrationtypes/EntryElement/settings',
            [
                'integration' => $this,
                'sectionsOptions' => $sections,
            ]
        );
    }

    public function submit(): bool
    {
        if (!$this->entryTypeId || Craft::$app->getRequest()->getIsCpRequest()) {
            return false;
        }

        $targetIntegrationFieldValues = $this->getTargetIntegrationFieldValues();

        /** @var EntryType $entryType */
        $entryType = Craft::$app->getSections()->getEntryTypeById($this->entryTypeId);

        $entryElement = new Entry();
        $entryElement->setTypeId($entryType->id);

        $entryElement->sectionId = $entryType->sectionId;

        $author = $this->getAuthor();

        if ($author !== null) {
            $entryElement->setAuthorId($author->id);
        }

        // @todo - why do we need to unset the id from the field mapping for this
        // Element Integration and not others? Consider refactoring the underlying
        // reason that causes the need to do this
        unset($targetIntegrationFieldValues['id']);

        $defaultAttributes = $this->getDefaultAttributes();

        $defaultAttributesHandles = [];
        foreach ($defaultAttributes as $defaultAttribute) {
            $defaultAttributesHandles[] = $defaultAttribute['handle'];
        }

        foreach ($targetIntegrationFieldValues as $fieldHandle => $fieldValue) {
            if (in_array($fieldHandle, $defaultAttributesHandles, true)) {
                $entryElement->{$fieldHandle} = $fieldValue;
            } else {
                $entryElement->setFieldValue($fieldHandle, $fieldValue);
            }
        }

        if ($entryElement->validate()) {
            $result = Craft::$app->getElements()->saveElement($entryElement);
            if ($result) {
                $this->successMessage = Craft::t('sprout-module-forms', 'Entry Element ID {id} created in {sectionName} Section', [
                    'id' => $entryElement->getId(),
                    'sectionName' => $entryElement->getSection()->name,
                ]);

                return true;
            }

            $message = Craft::t('sprout-module-forms', 'Unable to create Entry via Entry Element Integration');
            $this->addError('global', $message);
            Craft::error($message, __METHOD__);
        } else {
            $errors = json_encode($entryElement->getErrors(), JSON_THROW_ON_ERROR);
            $message = Craft::t('sprout-module-forms', 'Element Integration does not validate: ' . $this->name . ' - Errors: ' . $errors);
            Craft::error($message, __METHOD__);
            $this->addError('global', $entryElement->getErrors());
        }

        return false;
    }

    public function getTargetIntegrationFieldsAsMappingOptions(): array
    {
        $entryFields = $this->getElementCustomFieldsAsOptions($this->entryTypeId);

        return $this->getFieldsAsOptionsByRow($entryFields);
    }

    /**
     */
    public function getFieldsAsOptionsByRow(array $entryFields): array
    {
        $fieldMapping = $this->fieldMapping;
        $integrationSectionId = $this->entryTypeId ?? null;

        $formFields = $this->getSourceFormFieldsAsMappingOptions();
        $rowPosition = 0;
        $finalOptions = [];

        foreach ($formFields as $formField) {
            $optionsByRow = $this->getCompatibleFields($entryFields, $formField);
            // We have rows stored and are for the same sectionType
            if ($fieldMapping && ($integrationSectionId == $this->entryTypeId) &&
                isset($fieldMapping[$rowPosition])) {
                foreach ($optionsByRow as $key => $option) {
                    if ($option['value'] == $fieldMapping[$rowPosition]['targetIntegrationField'] &&
                        $fieldMapping[$rowPosition]['sourceFormField'] == $formField['value']) {
                        $optionsByRow[$key]['selected'] = true;
                    }
                }
            }

            $finalOptions[$rowPosition] = $optionsByRow;

            $rowPosition++;
        }

        return $finalOptions;
    }

    public function getCompatibleFields(array $entryFields, array $formField): array
    {
        $compatibleFields = $formField['compatibleCraftFields'] ?? '*';

        if (!is_array($compatibleFields)) {
            return [];
        }

        $finalOptions = [];

        foreach ($entryFields as $field) {
            if (!in_array($field::class, $compatibleFields, true)) {
                continue;
            }

            $finalOptions[] = [
                'label' => $field->name . ' (' . $field->handle . ')',
                'value' => $field->handle,
            ];
        }

        return $finalOptions;
    }

    public function getElementIntegrationFieldOptions(): array
    {
        $entryTypeId = $this->entryTypeId;

        // If no Entry ID has been selected, select the first one in the list.
        if ($entryTypeId === null || empty($entryTypeId)) {
            $sections = $this->getSectionsAsOptions();
            $entryTypeId = $sections[1]['value'] ?? null;
        }

        return $this->getElementCustomFieldsAsOptions($entryTypeId);
    }

    public function getDefaultAttributes(): array
    {
        $targetElementFieldsData = [
            [
                'label' => Craft::t('sprout-module-forms', 'Title'),
                'value' => 'title',
                'class' => PlainText::class,
            ],
            [
                'label' => Craft::t('sprout-module-forms', 'Slug'),
                'value' => 'slug',
                'class' => PlainText::class,
            ],
            [
                'label' => Craft::t('sprout-module-forms', 'Post Date'),
                'value' => 'postDate',
                'class' => Date::class,
            ],
            [
                'label' => Craft::t('sprout-module-forms', 'Date Created'),
                'value' => 'dateCreated',
                'class' => Date::class,
            ],
        ];

        $defaultFields = [];
        foreach ($targetElementFieldsData as $targetElementFieldData) {
            $fieldInstance = new $targetElementFieldData['class']();
            $fieldInstance->name = $targetElementFieldData['label'];
            $fieldInstance->handle = $targetElementFieldData['value'];
            $defaultFields[] = $fieldInstance;
        }

        return $defaultFields;
    }

    public function getElementCustomFieldsAsOptions($elementGroupId): array
    {
        $defaultEntryFields = $this->getDefaultElementFieldsAsOptions();
        $entryType = Craft::$app->getSections()->getEntryTypeById($elementGroupId);
        $entryFields = $entryType ? $entryType->getFields() : [];
        $options = $defaultEntryFields;

        foreach ($entryFields as $field) {
            $options[] = $field;
        }

        return $options;
    }

    public function getUserElementType(): string
    {
        return User::class;
    }

    /**
     * Returns the author who will be used when creating an Entry
     *
     * @return User|false|IdentityInterface|null
     */
    public function getAuthor()
    {
        $author = Craft::$app->getUser()->getIdentity();

        if ($this->setAuthorToLoggedInUser) {
            return $author;
        }

        if ($this->defaultAuthorId && is_array($this->defaultAuthorId)) {
            $user = Craft::$app->getUsers()->getUserById($this->defaultAuthorId[0]);
            if ($user) {
                $author = $user;
            }
        }

        return $author;
    }

    private function getSectionsAsOptions(): array
    {
        $sections = Craft::$app->getSections()->getAllSections();
        $options = [];

        foreach ($sections as $section) {
            // Don't show Singles
            if ($section->type === 'single') {
                continue;
            }

            $entryTypes = $section->getEntryTypes();

            $options[] = ['optgroup' => $section->name];

            foreach ($entryTypes as $entryType) {
                $options[] = [
                    'label' => $entryType->name,
                    'value' => $entryType->id,
                ];
            }
        }

        return $options;
    }
}
