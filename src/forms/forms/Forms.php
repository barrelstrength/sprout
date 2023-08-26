<?php

namespace BarrelStrength\Sprout\forms\forms;

use BarrelStrength\Sprout\forms\components\elements\FormElement;
use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use BarrelStrength\Sprout\forms\db\SproutTable;
use BarrelStrength\Sprout\forms\FormsModule;
use BarrelStrength\Sprout\forms\formtypes\FormTypeHelper;
use BarrelStrength\Sprout\forms\integrations\Integration;
use BarrelStrength\Sprout\forms\migrations\helpers\FormContentTableHelper;
use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\db\Query;
use craft\models\FieldLayoutTab;
use craft\records\FieldLayoutField;
use Exception;
use yii\base\Component;
use yii\db\Transaction;

class Forms extends Component
{
    protected static array $fieldVariables = [];

    public array $activeSubmissions = [];

    public ?SubmissionElement $activeCpSubmission = null;

    /**
     *
     * Allows a user to add variables to an object that can be parsed by fields
     *
     * @example
     * {% do sprout.forms.addFieldVariables({ submissionTitle: submission.title }) %}
     * {{ sprout.forms.displayForm('contact') }}
     */
    public static function addFieldVariables(array $variables): void
    {
        static::$fieldVariables = array_merge(static::$fieldVariables, $variables);
    }

    public static function getFieldVariables(): array
    {
        return static::$fieldVariables;
    }

    //public function saveForm(FormElement $form, bool $duplicate = false): bool
    //{
    //    $isNew = !$form->getId();
    //
    //    if (!$isNew) {
    //        // Add the oldHandle to our model so we can determine if we
    //        // need to rename the content table
    //        /** @var FormRecord $formRecord */
    //        $formRecord = FormRecord::findOne($form->getId());
    //        $form->oldHandle = $formRecord->getOldHandle();
    //        $oldForm = $formRecord;
    //
    //        if ($duplicate) {
    //            $form->name = $oldForm->name;
    //            $form->handle = $oldForm->handle;
    //            $form->oldHandle = null;
    //        }
    //    }
    //
    //    $form->validate();
    //
    //    if ($form->hasErrors()) {
    //        Craft::error($form->getErrors(), __METHOD__);
    //
    //        return false;
    //    }
    //
    //    /** @var Transaction $transaction */
    //    $transaction = Craft::$app->db->beginTransaction();
    //
    //    try {
    //        // Save the field layout
    //        $fieldLayout = $form->getFieldLayout();
    //        Craft::$app->getFields()->saveLayout($fieldLayout);
    //
    //        // Assign our new layout id info to our form model and record
    //        $form->setFieldLayoutId($fieldLayout->id);
    //
    //        // Set the field context
    //        Craft::$app->content->fieldContext = $form->getFieldContext();
    //        Craft::$app->content->contentTable = $form->getContentTable();
    //
    //        // Create the content table first since the form will need it
    //        $oldContentTable = $this->getContentTableName($form, true);
    //        $newContentTable = $this->getContentTableName($form);
    //
    //        // Do we need to create/rename the content table?
    //        if (!Craft::$app->db->tableExists($newContentTable) && !$duplicate) {
    //            if ($oldContentTable && Craft::$app->db->tableExists($oldContentTable)) {
    //                Db::renameTable($oldContentTable, $newContentTable);
    //            } else {
    //                $this->_createContentTable($newContentTable);
    //            }
    //        }
    //
    //        // Save the Form
    //        if (!Craft::$app->elements->saveElement($form)) {
    //            Craft::error('Couldn’t save Element.', __METHOD__);
    //
    //            return false;
    //        }
    //
    //        // FormRecord saved on afterSave form element
    //        $transaction->commit();
    //
    //        Craft::info('Form Saved.', __METHOD__);
    //    } catch (Exception $exception) {
    //        Craft::error('Unable to save form: ' . $exception->getMessage(), __METHOD__);
    //        $transaction->rollBack();
    //
    //        throw $exception;
    //    }
    //
    //    return true;
    //}

    /**
     * Removes a form and related records from the database
     */
    public function deleteForm(FormElement $form): bool
    {
        /** @var Transaction $transaction */
        $transaction = Craft::$app->db->beginTransaction();

        try {
            $submissionIds = (new Query())
                ->select(['submissions.id'])
                ->from(['submissions' => SproutTable::FORM_SUBMISSIONS])
                ->where([
                    '[[submissions.formId]]' => $form->id,
                ])
                ->column();

            foreach ($submissionIds as $submissionId) {
                Craft::$app->getElements()->deleteElementById($submissionId, SubmissionElement::class);
            }

            $fieldIds = FieldLayoutField::find()
                ->select(['fieldId'])
                ->where(['layoutId' => $form->submissionFieldLayoutId])
                ->column();

            // Delete form fields
            foreach ($fieldIds as $fieldId) {
                Craft::$app->getFields()->deleteFieldById($fieldId);
            }

            // Delete the Field Layout
            Craft::$app->getFields()->deleteLayoutById($form->submissionFieldLayoutId);

            $contentTable = FormContentTableHelper::getContentTable($form);

            // Drop the content table
            Craft::$app->getDb()->createCommand()
                ->dropTableIfExists($contentTable)
                ->execute();

            $success = Craft::$app->getElements()->deleteElementById($form->id, FormElement::class);

            if (!$success) {
                $transaction->rollBack();
                Craft::error('Couldn’t delete Form', __METHOD__);

                return false;
            }

            $transaction->commit();
        } catch (Exception $exception) {
            $transaction->rollBack();

            throw $exception;
        }

        return true;
    }

    /**
     * Returns an array of models for forms found in the database
     *
     * @return FormElement[]
     */
    public function getAllForms(int $siteId = null): array
    {
        $query = FormElement::find();
        $query->siteId($siteId);
        $query->orderBy(['name' => SORT_ASC]);

        return $query->all();
    }

    /**
     * Returns a form model if one is found in the database by id
     */
    public function getFormById(int $formId, int $siteId = null): ElementInterface|FormElement|null
    {
        $query = FormElement::find();
        $query->id($formId);
        $query->siteId($siteId);

        return $query->one();
    }

    /**
     * Returns a form model if one is found in the database by handle
     */
    public function getFormByHandle(string $handle, int $siteId = null): ElementInterface|FormElement|null
    {
        $query = FormElement::find();
        $query->handle($handle);
        $query->siteId($siteId);

        return $query->one();
    }

    /**
     * Returns the value of a given field
     */
    public function getFieldValue($field, $value): ?FormRecord
    {
        return FormRecord::findOne([
            $field => $value,
        ]);
    }

    /**
     * Remove a field handle from title format
     */
    public function cleanTitleFormat(int $fieldId): ?string
    {
        /** @var Field $field */
        $field = Craft::$app->getFields()->getFieldById($fieldId);

        if ($field) {
            $context = explode(':', $field->context);
            $formId = $context[1];

            /** @var FormRecord $formRecord */
            $formRecord = FormRecord::findOne($formId);

            // Check if the field is in the titleformat
            if (str_contains($formRecord->titleFormat, $field->handle)) {
                // Let's remove the field from the titleFormat
                $newTitleFormat = preg_replace('/{' . $field->handle . '.*}/', '', $formRecord->titleFormat);
                $formRecord->titleFormat = $newTitleFormat;
                $formRecord->save(false);

                return $formRecord->titleFormat;
            }
        }

        return null;
    }

    /**
     * IF a field is updated, update the integrations
     */
    public function updateFieldOnIntegrations($oldHandle, $newHandle, $form): void
    {
        $integrations = FormsModule::getInstance()->formIntegrations->getIntegrationsByFormId($form->id);

        /** @var Integration $integration */
        foreach ($integrations as $integration) {
            $integrationResult = (new Query())
                ->select(['id', 'settings'])
                ->from([SproutTable::FORM_INTEGRATIONS])
                ->where(['id' => $integration->id])
                ->one();

            if ($integrationResult === null) {
                continue;
            }

            $settings = json_decode($integrationResult['settings'], true, 512, JSON_THROW_ON_ERROR);

            $fieldMapping = $settings['fieldMapping'];
            foreach ($fieldMapping as $pos => $map) {
                if (isset($map['sourceFormField']) && $map['sourceFormField'] === $oldHandle) {
                    $fieldMapping[$pos]['sourceFormField'] = $newHandle;
                }
            }

            $integration->fieldMapping = $fieldMapping;
            FormsModule::getInstance()->formIntegrations->saveIntegration($integration);
        }
    }

    /**
     * Update a field handle with an new title format
     */
    public function updateTitleFormat(string $oldHandle, string $newHandle, string $titleFormat): string
    {
        return str_replace($oldHandle, $newHandle, $titleFormat);
    }

    /**
     * Create a secuencial string for the "name" and "handle" fields if they are already taken
     */
    public function getFieldAsNew($field, $value): string
    {
        $i = 1;
        $band = true;

        do {
            $newField = $field == 'handle' ? $value . $i : $value . ' ' . $i;
            $form = $this->getFieldValue($field, $newField);
            if (!$form instanceof FormRecord) {
                $band = false;
            }

            $i++;
        } while ($band);

        return $newField;
    }

    /**
     * Removes forms and related records from the database given the ids
     */
    public function deleteForms($formElements): bool
    {
        foreach ($formElements as $formElement) {
            $form = FormsModule::getInstance()->forms->getFormById($formElement->id);

            if ($form !== null) {
                FormsModule::getInstance()->forms->deleteForm($form);
            } else {
                Craft::error("Can't delete the form with id: {$formElement->id}", __METHOD__);
            }
        }

        return true;
    }

    /**
     * Creates a form with a empty default tab
     */
    public function createNewForm($name = null, $handle = null): ?FormElement
    {
        $form = new FormElement();
        $name ??= 'Form';
        $handle ??= 'form';

        $settings = FormsModule::getInstance()->getSettings();

        $form->name = $this->getFieldAsNew('name', $name);
        $form->handle = $this->getFieldAsNew('handle', $handle);
        $form->titleFormat = "{dateCreated|date('D, d M Y H:i:s')}";
        $form->formTypeUid = FormTypeHelper::getDefaultFormType();
        $form->saveData = $settings->enableSaveData && $settings->enableSaveDataDefaultValue;

        // Set default tab

        /** @var Field $field */
        //        $field = null;
        //        $form = FormsModule::getInstance()->formFields->addDefaultTab($form, $field);
        //
        //        if ($this->saveForm($form)) {
        //            // Let's delete the default field
        //            if ($field !== null && $field->id) {
        //                Craft::$app->getFields()->deleteFieldById($field->id);
        //            }
        //
        //            return $form;
        //        }
        //
        //        return null;
    }

    /**
     * Checks if the current plugin edition allows a user to create a Form
     */
    public function canCreateForm(): bool
    {
        if (!FormsModule::isPro()) {
            $forms = $this->getAllForms();

            if (count($forms) >= 1) {
                return false;
            }
        }

        return true;
    }

    public function getTabsForFieldLayout(FormElement $form): array
    {
        $tabs = [];

        $fieldLayout = $form->getFieldLayout();
        $fieldLayoutTabs = $fieldLayout->getTabs();
        if (empty($fieldLayoutTabs)) {
            $fieldLayoutTabs[] = new FieldLayoutTab([
                'name' => FormsModule::getInstance()->formFields->getDefaultTabName(),
                'sortOrder' => 1,
            ]);
            $fieldLayout->setTabs($fieldLayoutTabs);
            Craft::$app->getFields()->saveLayout($fieldLayout);
        }

        foreach ($fieldLayoutTabs as $tab) {
            // Do any of the fields on this tab have errors?
            $hasErrors = false;

            if ($form->hasErrors()) {
                foreach ($tab->getFields() as $field) {
                    /** @var Field $field */
                    if ($hasErrors = $form->hasErrors($field->handle . '.*')) {
                        break;
                    }
                }
            }

            $tabs[$tab->id] = [
                'label' => Craft::t('sprout-module-forms', $tab->name),
                'url' => '#sproutforms-tab-' . $tab->id,
                'class' => $hasErrors ? 'error' : null,
            ];
        }

        return $tabs;
    }

    //public function getFormField($formFieldHandle, $formId)
    //{
    //    $form = Craft::$app->elements->getElementById($formId);
    //
    //    if (!$form) {
    //        throw new BadRequestHttpException('No form exists with the ID ' . $formId);
    //    }
    //
    //    return $form->getField($formFieldHandle);
    //}

    public function handleModifyFormHook($context): ?string
    {
        /** @var FormElement $form */
        $form = $context['form'] ?? null;
        if ($form !== null && $form->enableCaptchas) {
            return FormsModule::getInstance()->formCaptchas->getCaptchasHtml($form);
        }

        return null;
    }
}
