<?php

namespace BarrelStrength\Sprout\forms\controllers;

use BarrelStrength\Sprout\forms\components\elements\FormElement;
use BarrelStrength\Sprout\forms\formfields\FormField;
use BarrelStrength\Sprout\forms\FormsModule;
use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\FieldLayoutElement;
use craft\errors\ElementNotFoundException;
use craft\fieldlayoutelements\CustomField;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\models\FieldLayoutTab;
use craft\web\Controller as BaseController;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class FormFieldsController extends BaseController
{
    /**
     * Load the modal field template.
     */
    public function actionModalField(): Response
    {
        $this->requireAcceptsJson();
        $this->requirePermission(FormsModule::p('editForms'));

        $formId = Craft::$app->getRequest()->getBodyParam('formId');
        $form = FormsModule::getInstance()->forms->getFormById($formId);

        if (!$form instanceof ElementInterface) {
            throw new ElementNotFoundException('Form not found.');
        }

        return $this->asJson(FormsModule::getInstance()->formFields->getModalFieldTemplate($form));
    }

    /**
     * This action allows create a default field given a type.
     */
    public function actionCreateField(): Response
    {
        $this->requireAcceptsJson();
        $this->requirePermission(FormsModule::p('editForms'));

        $request = Craft::$app->getRequest();
        $type = $request->getBodyParam('type');
        $tabUid = $request->getBodyParam('tabUid');

        $formId = $request->getBodyParam('formId');
        $nextId = $request->getBodyParam('nextId');

        $form = FormsModule::getInstance()->forms->getFormById($formId);
        $field = null;

        $tab = $form ? ArrayHelper::firstWhere($form->getFieldLayout()->getTabs(), 'id', $tabUid) : null;

        if ($type && $form && $tab) {
            /** @var Field $field */
            $field = FormsModule::getInstance()->formFields->createDefaultField($type, $form);

            if ($field) {
                // Set the field layout
                $oldFieldLayout = $form->getFieldLayout();
                $oldTabs = $oldFieldLayout->getTabs();

                if ($oldTabs) {
                    // it's a new field
                    $response = FormsModule::getInstance()->formFields->addFieldToLayout($field, $form, $tabUid, $nextId);

                    return $this->returnJson($response, $field, $form, $tab->name, $tabUid);
                }
            }
        }

        return $this->returnJson(false, $field, $form, null, $tabUid);
    }

    public function actionSaveField(): Response
    {
        $variables = [];
        $this->requirePostRequest();
        $this->requirePermission(FormsModule::p('editForms'));

        $request = Craft::$app->getRequest();
        $fieldsService = Craft::$app->getFields();

        $tabUid = $request->getBodyParam('tabUid');
        $formId = $request->getRequiredBodyParam('formId');
        $form = FormsModule::getInstance()->forms->getFormById($formId);

        if (!$form instanceof ElementInterface) {
            throw new NotFoundHttpException('Form not found.');
        }

        $type = $request->getRequiredBodyParam('type');
        $fieldId = $request->getBodyParam('fieldId');
        $fieldsService = Craft::$app->getFields();
        /** @var Field $field */
        $field = $fieldsService->createField([
            'type' => $type,
            'id' => $fieldId,
            'name' => $request->getBodyParam('name'),
            'handle' => $request->getBodyParam('handle'),
            'instructions' => $request->getBodyParam('instructions'),
            // @todo - confirm locales/Sites work as expected
            'translationMethod' => Field::TRANSLATION_METHOD_NONE,
            'settings' => $request->getBodyParam('types.' . $type),
        ]);

        // Set our field context
        Craft::$app->content->fieldContext = $form->getSubmissionFieldContext();
        Craft::$app->content->contentTable = $form->getSubmissionContentTable();

        // Save a new field
        if ($field->id) {
            $isNewField = false;
            /** @var Field $oldField */
            $oldField = Craft::$app->fields->getFieldById($field->id);
            $oldHandle = $oldField->handle;
        } else {
            $isNewField = true;
            $oldHandle = null;
        }

        // Save our field
        if (!$fieldsService->saveField($field)) {
            // Does not validate
            Craft::error('Field does not validate.', __METHOD__);

            $variables['tabUid'] = $tabUid;
            $variables['field'] = $field;

            return $this->returnJson(false, $field, $form, null, $tabUid);
        }

        // Check if the handle is updated to also update the titleFormat, rules and integrations
        if (!$isNewField && $oldHandle !== $field->handle) {
            if (str_contains($form->titleFormat, $oldHandle)) {
                $newTitleFormat = FormsModule::getInstance()->forms->updateTitleFormat($oldHandle, $field->handle, $form->titleFormat);
                $form->titleFormat = $newTitleFormat;
            }

            FormsModule::getInstance()->forms->updateFieldOnFieldRules($oldHandle, $field->handle, $form);
            FormsModule::getInstance()->forms->updateFieldOnIntegrations($oldHandle, $field->handle, $form);
        }

        // Now let's add this field to our field layout
        // ------------------------------------------------------------

        // Set the field layout
        $oldFieldLayout = $form->getFieldLayout();
        $oldTabs = $oldFieldLayout->getTabs();
        $tabName = null;
        $response = false;

        if ($oldTabs) {
            /** @var FieldLayoutTab $tab */
            $tab = ArrayHelper::firstWhere($form->getFieldLayout()->getTabs(), 'id', $tabUid);
            $tabName = $tab->name;
            $required = $request->getBodyParam('required');

            if ($isNewField) {
                $response = FormsModule::getInstance()->formFields->addFieldToLayout($field, $form, $tabUid, null, $required);
            } else {
                $response = FormsModule::getInstance()->formFields->updateFieldToLayout($field, $form, $tabUid, $required);
            }
        }

        // Hand the field off to be saved in the
        // field layout of our Form Element
        if ($response) {
            Craft::info('Field Saved', __METHOD__);

            return $this->returnJson(true, $field, $form, $tabName, $tabUid);
        }

        $variables['tabUid'] = $tabUid;
        $variables['field'] = $field;
        Craft::error("Couldn't save field.", __METHOD__);
        Craft::$app->getSession()->setError(Craft::t('sprout-module-forms', 'Couldn’t save field.'));

        return $this->returnJson(false, $field, $form);
    }

    public function actionEditField(): Response
    {
        $this->requireAcceptsJson();
        $this->requirePermission(FormsModule::p('editForms'));

        $request = Craft::$app->getRequest();

        $id = $request->getBodyParam('fieldId');
        $formId = $request->getBodyParam('formId');

        /** @var FormElement $form */
        $form = FormsModule::getInstance()->forms->getFormById($formId);
        /** @var Field $field */
        $field = Craft::$app->fields->getFieldById($id);

        if ($field) {
            // Find the field in the field layout
            foreach ($form->getFieldLayout()->getTabs() as $tab) {
                /** @var CustomField|null $fieldElement */
                $fieldElement = ArrayHelper::firstWhere($tab->getElements(), static function(FieldLayoutElement $element) use ($field): bool {
                    return $element instanceof CustomField && $element->getField()->id == $field->id;
                });
                if ($fieldElement !== null) {
                    $field->required = $fieldElement->required;

                    return $this->asJson([
                        'success' => true,
                        'errors' => $field->getErrors(),
                        'field' => [
                            'id' => $field->id,
                            'name' => $field->name,
                            'handle' => $field->handle,
                            'instructions' => $field->instructions,
                            'required' => $field->required,
                            //'translatable' => $field->translatable,
                            'group' => [
                                'name' => $tab->name,
                            ],
                        ],
                        'template' => FormsModule::getInstance()->formFields->getModalFieldTemplate($form, $field, $tab->id),
                    ]);
                }
            }
        }

        $message = Craft::t('sprout-module-forms', 'The field requested to edit no longer exists.');
        Craft::error($message, __METHOD__);

        return $this->asJson([
            'success' => false,
            'error' => $message,
        ]);
    }

    public function actionDeleteField(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $this->requirePermission(FormsModule::p('editForms'));

        $fieldId = Craft::$app->request->getRequiredBodyParam('fieldId');

        /** @var Field $field */
        $field = Craft::$app->fields->getFieldById($fieldId);
        $oldHandle = $field->handle;
        $formId = Craft::$app->request->getRequiredBodyParam('formId');

        /** @var FormElement $form */
        $form = FormsModule::getInstance()->forms->getFormById((int)$formId);

        // Backup our field context and content table
        $oldFieldContext = Craft::$app->getContent()->fieldContext;
        $oldContentTable = Craft::$app->getContent()->contentTable;

        // Set our field content and content table to work with our form output
        Craft::$app->getContent()->fieldContext = $form->getSubmissionFieldContext();
        Craft::$app->getContent()->contentTable = $form->getSubmissionContentTable();

        $response = Craft::$app->fields->deleteFieldById($fieldId);

        // Reset our field context and content table to what they were previously
        Craft::$app->getContent()->fieldContext = $oldFieldContext;
        Craft::$app->getContent()->contentTable = $oldContentTable;

        if ($response) {
            FormsModule::getInstance()->forms->removeFieldRulesUsingField($oldHandle, $form);

            return $this->asJson([
                'success' => true,
            ]);
        }

        return $this->asJson([
            'success' => false,
        ]);
    }

    public function actionReorderFields(): Response
    {
        $this->requireAdmin(false);
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $this->requirePermission(FormsModule::p('editForms'));

        $fieldIds = Json::decode(Craft::$app->request->getRequiredBodyParam('ids'));
        FormsModule::getInstance()->formFields->reorderFields($fieldIds);

        return $this->asJson([
            'success' => true,
        ]);
    }

    private function returnJson(bool $success, $field, FormElement $form, $tabName = null, $tabUid = null): Response
    {
        /** @var FormField $field */
        return $this->asJson([
            'success' => $success,
            'errors' => $field ? $field->getErrors() : null,
            'field' => [
                'id' => $field->id,
                'name' => $field->name,
                'handle' => $field->handle,
                'icon' => $field->getSvgIconPath(),
                'htmlExample' => $field->getExampleInputHtml(),
                'required' => $field->required,
                'instructions' => $field->instructions,
                'group' => [
                    'name' => $tabName,
                    'id' => $tabUid,
                ],
                'uid' => $field->uid,
            ],
            'template' => $success ? false : FormsModule::getInstance()->formFields->getModalFieldTemplate($form, $field),
        ]);
    }
}
