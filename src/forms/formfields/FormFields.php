<?php

namespace BarrelStrength\Sprout\forms\formfields;

use BarrelStrength\Sprout\forms\components\elements\FormElement;
use BarrelStrength\Sprout\forms\components\formfields\AddressFormField;
use BarrelStrength\Sprout\forms\components\formfields\CategoriesFormField;
use BarrelStrength\Sprout\forms\components\formfields\CheckboxesFormField;
use BarrelStrength\Sprout\forms\components\formfields\CustomHtmlFormField;
use BarrelStrength\Sprout\forms\components\formfields\DateFormField;
use BarrelStrength\Sprout\forms\components\formfields\DropdownFormField;
use BarrelStrength\Sprout\forms\components\formfields\EmailDropdownFormField;
use BarrelStrength\Sprout\forms\components\formfields\EmailFormField;
use BarrelStrength\Sprout\forms\components\formfields\EntriesFormField;
use BarrelStrength\Sprout\forms\components\formfields\FileUploadFormField;
use BarrelStrength\Sprout\forms\components\formfields\GenderFormField;
use BarrelStrength\Sprout\forms\components\formfields\HiddenFormField;
use BarrelStrength\Sprout\forms\components\formfields\InvisibleFormField;
use BarrelStrength\Sprout\forms\components\formfields\MultipleChoiceFormField;
use BarrelStrength\Sprout\forms\components\formfields\MultiSelectFormField;
use BarrelStrength\Sprout\forms\components\formfields\NameFormField;
use BarrelStrength\Sprout\forms\components\formfields\NumberFormField;
use BarrelStrength\Sprout\forms\components\formfields\OptInFormField;
use BarrelStrength\Sprout\forms\components\formfields\ParagraphFormField;
use BarrelStrength\Sprout\forms\components\formfields\PhoneFormField;
use BarrelStrength\Sprout\forms\components\formfields\PrivateNotesFormField;
use BarrelStrength\Sprout\forms\components\formfields\RegularExpressionFormField;
use BarrelStrength\Sprout\forms\components\formfields\SectionHeadingFormField;
use BarrelStrength\Sprout\forms\components\formfields\SingleLineFormField;
use BarrelStrength\Sprout\forms\components\formfields\TagsFormField;
use BarrelStrength\Sprout\forms\components\formfields\UrlFormField;
use BarrelStrength\Sprout\forms\components\formfields\UsersFormField;
use BarrelStrength\Sprout\forms\FormsModule;
use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\FieldInterface;
use craft\db\Query;
use craft\db\Table;
use craft\errors\ElementNotFoundException;
use craft\events\RegisterComponentTypesEvent;
use craft\fieldlayoutelements\CustomField;
use craft\helpers\ArrayHelper;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\records\Field as FieldRecord;
use craft\records\FieldLayoutField as FieldLayoutFieldRecord;
use craft\records\FieldLayoutTab as FieldLayoutTabRecord;
use yii\base\Component;
use yii\base\Exception;

/**
 * @property mixed $defaultTabName
 * @property array $registeredFieldsByGroup
 */
class FormFields extends Component
{
    public const EVENT_REGISTER_FORM_FIELDS = 'registerSproutFormFields';

    /**
     * @var FormFieldTrait[]
     */
    protected ?array $_formFields = null;

    public function getFormFieldTypes(): array
    {
        if ($this->_formFields) {
            return $this->_formFields;
        }

        $formFields = FormsModule::getInstance()->formFields->getDefaultFormFieldTypes();

        $event = new RegisterComponentTypesEvent([
            'types' => $formFields,
        ]);

        $this->trigger(self::EVENT_REGISTER_FORM_FIELDS, $event);

        /** @var FormFieldTrait $instance */
        foreach ($event->types as $type) {
            $this->_formFields[$type] = $type;
        }

        return $this->_formFields;
    }

    public function reorderFields($fieldIds): bool
    {
        $transaction = Craft::$app->db->getTransaction() === null ? Craft::$app->db->beginTransaction() : null;

        try {
            foreach ($fieldIds as $fieldOrder => $fieldId) {
                $fieldLayoutFieldRecord = $this->getFieldLayoutFieldRecordByFieldId($fieldId);
                $fieldLayoutFieldRecord->sortOrder = $fieldOrder + 1;
                $fieldLayoutFieldRecord->save();
            }

            if ($transaction !== null) {
                $transaction->commit();
            }
        } catch (Exception $exception) {

            if ($transaction !== null) {
                $transaction->rollBack();
            }

            throw $exception;
        }

        return true;
    }

    /**
     * This service allows duplicate fields from Layout
     */
    public function getDuplicateLayout(FormElement $form, FieldLayout $postFieldLayout): ?FieldLayout
    {
        if (!$form || !$postFieldLayout) {
            return null;
        }

        $oldTabs = $postFieldLayout->getTabs();
        $tabs = [];
        $fields = [];

        foreach ($oldTabs as $oldTab) {
            /** @var Field[] $fieldLayoutFields */
            $fieldLayoutFields = $oldTab->getFields();
            $tabFields = [];

            foreach ($fieldLayoutFields as $fieldLayoutField) {

                /** @var Field $field */
                $field = Craft::$app->getFields()->createField([
                    'type' => $fieldLayoutField::class,
                    'name' => $fieldLayoutField->name,
                    'handle' => $fieldLayoutField->handle,
                    'instructions' => $fieldLayoutField->instructions,
                    'required' => $fieldLayoutField->required,
                    'settings' => $fieldLayoutField->getSettings(),
                ]);

                Craft::$app->content->fieldContext = $form->getFieldContext();
                Craft::$app->content->contentTable = $form->getContentTable();

                // Save duplicate field
                Craft::$app->fields->saveField($field);

                $fields[] = $field;
                $tabFields[] = $field;
            }

            $newTab = new FieldLayoutTab();
            $newTab->name = urldecode($oldTab->name);
            $newTab->sortOrder = 0;
            $newTab->setFields($tabFields);

            $tabs[] = $newTab;
        }

        $fieldLayout = new FieldLayout();
        $fieldLayout->type = FormElement::class;
        $fieldLayout->setTabs($tabs);
        $fieldLayout->setFields($fields);

        return $fieldLayout;
    }

    public function getDefaultFormFieldTypes(): array
    {
        $fields = [];
        $fieldsByGroup = $this->getDefaultFormFieldTypesByGroup();
        foreach ($fieldsByGroup as $group) {
            foreach ($group as $type) {
                $fields[] = $type;
            }
        }

        return $fields;
    }

    public function getDefaultFormFieldTypesByGroup(): array
    {
        $groupedFields = [];
        $standardLabel = 'standard';
        $specialLabel = 'special';
        $relationsLabel = 'relations';
        $layoutElements = 'layoutElements';

        // Standard
        $groupedFields[$standardLabel][] = SingleLineFormField::class;
        $groupedFields[$standardLabel][] = ParagraphFormField::class;
        $groupedFields[$standardLabel][] = MultipleChoiceFormField::class;
        $groupedFields[$standardLabel][] = DropdownFormField::class;
        $groupedFields[$standardLabel][] = CheckboxesFormField::class;
        $groupedFields[$standardLabel][] = MultiSelectFormField::class;
        $groupedFields[$standardLabel][] = FileUploadFormField::class;
        $groupedFields[$standardLabel][] = DateFormField::class;
        $groupedFields[$standardLabel][] = NumberFormField::class;
        $groupedFields[$standardLabel][] = RegularExpressionFormField::class;
        $groupedFields[$standardLabel][] = HiddenFormField::class;
        $groupedFields[$standardLabel][] = InvisibleFormField::class;

        // Special
        $groupedFields[$specialLabel][] = NameFormField::class;
        $groupedFields[$specialLabel][] = AddressFormField::class;
        $groupedFields[$specialLabel][] = EmailFormField::class;
        $groupedFields[$specialLabel][] = EmailDropdownFormField::class;
        $groupedFields[$specialLabel][] = UrlFormField::class;
        $groupedFields[$specialLabel][] = PhoneFormField::class;
        $groupedFields[$specialLabel][] = OptInFormField::class;
        $groupedFields[$specialLabel][] = GenderFormField::class;

        // Relations
        $groupedFields[$relationsLabel][] = CategoriesFormField::class;
        $groupedFields[$relationsLabel][] = EntriesFormField::class;
        $groupedFields[$relationsLabel][] = TagsFormField::class;
        $groupedFields[$relationsLabel][] = UsersFormField::class;

        // UI Fields
        $groupedFields[$layoutElements][] = SectionHeadingFormField::class;
        $groupedFields[$layoutElements][] = CustomHtmlFormField::class;
        $groupedFields[$layoutElements][] = PrivateNotesFormField::class;

        return $groupedFields;
    }

    public function getCustomFields($registeredFields, $sproutFormsFields)
    {
        foreach ($sproutFormsFields as $group) {
            foreach ($group as $field) {
                unset($registeredFields[$field]);
            }
        }

        return $registeredFields;
    }

    public function getFieldCondition($conditionClass, $formField): Condition
    {
        return new $conditionClass(['formField' => $formField]);
    }

    public function getFieldValue(string $field, string $value): ?FieldRecord
    {
        return FieldRecord::findOne([
            $field => $value,
        ]);
    }

    /**
     * Create a sequential string for the "name" and "handle" fields if they are already taken
     *
     * @return null|string|string[]
     */
    public function getFieldAsNew($field, $value)
    {
        $i = 1;
        $band = true;

        do {
            if ($field == 'handle') {
                // Append a number to our handle to ensure it is unique
                $newField = $value . $i;

                $form = $this->getFieldValue($field, $newField);

                if (!$form instanceof FieldRecord) {
                    $band = false;
                }
            } else {
                // Add spaces before any capital letters in our name
                $newField = preg_replace('#([a-z])([A-Z])#', '$1 $2', $value);
                $band = false;
            }

            $i++;
        } while ($band);

        return $newField;
    }

    /**
     * This service allows create a default tab given a form
     *
     * @param Field|FieldInterface|null $field
     */
    public function addDefaultTab(FormElement $form, &$field = null): ?FormElement
    {
        $tabs = [];
        if (!$form) {
            return null;
        }

        $fields = [];
        $tabFields = [];

        if ($field === null) {
            $fieldsService = Craft::$app->getFields();
            $handle = $this->getFieldAsNew('handle', 'defaultField');

            $field = $fieldsService->createField([
                'type' => SingleLineFormField::class,
                'name' => Craft::t('sprout-module-forms', 'Default Field'),
                'handle' => $handle,
                'instructions' => '',
                'translationMethod' => Field::TRANSLATION_METHOD_NONE,
            ]);
            // Save our field
            Craft::$app->content->fieldContext = $form->getFieldContext();
            Craft::$app->fields->saveField($field);

            $fields[] = $field;
            $tabFields[] = $field;
        }

        // Create a tab
        $tabName = $this->getDefaultTabName();

        $tab = new FieldLayoutTab();
        $tab->name = urldecode($tabName);
        $tab->sortOrder = 0;
        $tab->setFields($tabFields);

        $tabs[] = $tab;

        $fieldLayout = new FieldLayout();
        $fieldLayout->type = FormElement::class;
        $fieldLayout->setTabs($tabs);
        $fieldLayout->setFields($fields);

        // Set the tab to the form
        $form->setFieldLayout($fieldLayout);

        return $form;
    }

    /**
     * This service allows add a field to a current FieldLayoutFieldRecord
     */
    public function addFieldToLayout(Field $field, FormElement $form, int $tabId, $nextId = null, bool $required = false): bool
    {
        $layout = $form->getFieldLayout();
        $tab = ArrayHelper::firstWhere($layout->getTabs(), 'id', $tabId);

        if (!$tab instanceof FieldLayoutTab) {
            Craft::warning("Invalid field layout tab ID: {$tabId}", __METHOD__);

            return false;
        }

        $fieldElement = new CustomField($field, [
            'required' => $required,
        ]);

        $placed = false;

        if ($nextId) {
            foreach ($tab->getElements() as $i => $element) {
                if ($element instanceof CustomField && $element->getField()->id == $nextId) {
                    $tab->getElements();
                    array_splice($getElements, $i, 0, [$fieldElement]);
                    $placed = true;
                    break;
                }
            }
        }

        if (!$placed) {
            $tab->elements[] = $fieldElement;
        }

        return Craft::$app->getFields()->saveLayout($layout);
    }

    /**
     * This service allows update a field to a current FieldLayoutFieldRecord
     */
    public function updateFieldToLayout(Field $field, FormElement $form, int $tabId, bool $required = false): bool
    {
        $layout = $form->getFieldLayout();

        // Find and update/remove the current field element
        foreach ($layout->getTabs() as $tab) {
            foreach ($tab->getElements() as $i => $element) {
                if ($element instanceof CustomField && $element->getField()->id == $field->id) {
                    if ($tab->id == $tabId) {
                        // The field is already where it needs to be.
                        // Just update its `required` setting and save.
                        $element->required = $required;

                        return Craft::$app->getFields()->saveLayout($layout);
                    }

                    // It's in the wrong tab so remove it
                    unset($tab->getElements()[$i]);
                    break 2;
                }
            }
        }

        // Append the field to the expected tab
        return $this->addFieldToLayout($field, $form, $tabId, null, $required);
    }

    public function getDefaultTabName(): string
    {
        return Craft::t('sprout-module-forms', 'Page 1');
    }

    /**
     * Loads the sprout modal field via ajax.
     */
    public function getModalFieldTemplate(FormElement $form, $field = null, $tabId = null): array
    {
        $fieldsService = Craft::$app->getFields();
        $request = Craft::$app->getRequest();

        $data = [];
        $data['tabId'] = null;
        $data['field'] = $fieldsService->createField(SingleLineFormField::class);

        if ($field !== null) {
            $data['field'] = $field;
            $tabIdByPost = $request->getBodyParam('tabId');

            if ($tabIdByPost !== null) {
                $data['tabId'] = $tabIdByPost;
            } elseif ($tabId !== null) {
                //edit field
                $data['tabId'] = $tabId;
            }

            if ($field->id != null) {
                $data['fieldId'] = $field->id;
            }
        }

        $data['sections'] = $form->getFieldLayout()->getTabs();
        $data['form'] = $form;
        $data['fieldClass'] = $data['field']::class ?? null;
        $view = Craft::$app->getView();

        $html = $view->renderTemplate('sprout-module-forms/forms/_editFieldModal', $data);
        $js = $view->getBodyHtml();
        $css = $view->getHeadHtml();

        return [
            'html' => $html,
            'js' => $js,
            'css' => $css,
        ];
    }

    public function createDefaultField($type, FormElement $form): FieldInterface
    {
        /** @var FieldInterface $instanceField */
        $instanceField = new $type;
        $fieldsService = Craft::$app->getFields();
        // get the field name and remove spaces
        $fieldName = preg_replace('#\s+#', '', $instanceField::displayName());
        // strip all non-alphanumeric characters
        $fieldName = preg_replace('#[^A-Za-z0-9 ]#', '', $fieldName);

        $handleName = StringHelper::toCamelCase(lcfirst($fieldName));
        $name = $this->getFieldAsNew('name', $fieldName);
        $handle = $this->getFieldAsNew('handle', $handleName);

        $field = $fieldsService->createField([
            'type' => $type,
            'name' => $name,
            'handle' => $handle,
            'instructions' => '',
            // @todo - test locales/sites behavior
            'translationMethod' => Field::TRANSLATION_METHOD_NONE,
        ]);

        // Set our field context
        Craft::$app->content->fieldContext = $form->getFieldContext();
        Craft::$app->content->contentTable = $form->getContentTable();

        $fieldsService->saveField($field);

        return $field;
    }

    public function createNewTab(int $formId, $name): FieldLayoutTabRecord
    {
        $form = FormsModule::getInstance()->forms->getFormById($formId);

        if (!$form instanceof ElementInterface) {
            throw new ElementNotFoundException('No Form exists with id ' . $form->id);
        }

        $fieldLayout = $form->getFieldLayout();

        $maxSortOrder = (new Query())
            ->select('sortOrder')
            ->from(Table::FIELDLAYOUTTABS)
            ->where([
                'layoutId' => $fieldLayout->id,
            ])
            ->orderBy('sortOrder desc')
            ->scalar();

        // Place after other tabs
        $sortOrder = (int)$maxSortOrder + 1;

        $tabRecord = new FieldLayoutTabRecord();
        $tabRecord->name = strip_tags($name);
        $tabRecord->sortOrder = $sortOrder;
        $tabRecord->layoutId = $fieldLayout->id;

        $tabRecord->save();

        return $tabRecord;
    }

    /**
     * Renames tab of form layout
     */
    public function renameTab($tabId, $newName): bool
    {
        $response = false;

        $tabRecord = FieldLayoutTabRecord::findOne($tabId);

        if ($tabRecord !== null) {
            $tabRecord->name = $newName;
            $response = $tabRecord->save(false);
        }

        return $response;
    }

    public function deleteTab(FormElement $form, FieldLayoutTabRecord $tabRecord): bool
    {
        $fieldLayout = $form->getFieldLayout();

        if (count($fieldLayout->getTabs()) <= 1) {
            $tabRecord->addError('submissionFieldLayoutId', Craft::t('sprout-module-forms', 'Unable to delete page. One page required.'));

            return false;
        }

        $tabRecord->delete();

        return !$tabRecord->hasErrors();
    }

    public function getFieldLayoutTabs($layoutId): array
    {
        return (new Query())
            ->select('*')
            ->from(Table::FIELDLAYOUTTABS)
            ->where([
                'layoutId' => $layoutId,
            ])
            ->orderBy('sortOrder asc')
            ->all();
    }

    /**
     * Prepends a key/value pair to an array
     *
     * @see array_unshift()
     */
    public function prependKeyValue(array $haystack, string $key, $value): array
    {
        $haystack = array_reverse($haystack, true);
        $haystack[$key] = $value;

        return array_reverse($haystack, true);
    }

    protected function getFieldLayoutFieldRecordByFieldId($fieldId = null): FieldLayoutFieldRecord
    {
        if ($fieldId) {
            /** @var FieldLayoutFieldRecord $fieldLayoutFieldRecord */
            $fieldLayoutFieldRecord = FieldLayoutFieldRecord::find()
                ->where('fieldId=:fieldId', [
                    ':fieldId' => $fieldId,
                ]);

            if (!$fieldLayoutFieldRecord) {
                throw new Exception('No field exists with the ID ' . $fieldId);
            }

            return $fieldLayoutFieldRecord;
        }

        return new FieldLayoutFieldRecord();
    }
}
