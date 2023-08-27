<?php

namespace BarrelStrength\Sprout\forms\controllers;

use BarrelStrength\Sprout\forms\components\elements\FormElement;
use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use BarrelStrength\Sprout\forms\forms\FormBuilderHelper;
use BarrelStrength\Sprout\forms\FormsModule;
use BarrelStrength\Sprout\forms\formtypes\FormTypeHelper;
use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\errors\WrongEditionException;
use craft\fields\MissingField;
use craft\helpers\Cp;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\models\FieldLayoutTab;
use craft\models\Site;
use craft\records\FieldLayout as FieldLayoutRecord;
use craft\web\Controller as BaseController;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

class FormsController extends BaseController
{
    public function actionFormsIndexTemplate(): Response
    {
        $this->requirePermission(FormsModule::p('editForms'));

        $formTypes = FormTypeHelper::getFormTypes();

        return $this->renderTemplate('sprout-module-forms/forms/index', [
            'title' => FormElement::pluralDisplayName(),
            'elementType' => FormElement::class,
            'formTypes' => $formTypes,
            'selectedSubnavItem' => 'forms',
        ]);
    }

    public function actionEditSettingsTemplate(int $formId = null, $subNavKey = null): Response
    {
        $this->requirePermission(FormsModule::p('editForms'));

        $form = FormsModule::getInstance()->forms->getFormById($formId);

        $config = FormsModule::getInstance()->getSettings();

        return $this->renderTemplate('sprout-module-forms/forms/_settings/' . $subNavKey, [
            'form' => $form,
            'settings' => FormsModule::getInstance()->getSettings(),
            'rules' => FormsModule::getInstance()->formRules->getRulesByFormId($formId),
            'ruleOptions' => FormsModule::getInstance()->formRules->getRuleOptions(),
            'integrations' => FormsModule::getInstance()->formIntegrations->getIntegrationsByFormId($formId),
            'config' => $config,
        ]);
    }

    //public function actionDuplicateForm()
    //{
    //    $this->requirePermission(FormsModule::p('editForms'));
    //
    //    return $this->runAction('save-form', ['duplicate' => true]);
    //}

    //public function actionSaveForm(bool $duplicate = false): ?Response
    //{
    //    $this->requirePostRequest();
    //    $this->requirePermission(FormsModule::p('editForms'));
    //
    //    $request = Craft::$app->getRequest();
    //
    //    $form = $this->getFormModel();
    //    $oldTitleFormat = $form->titleFormat;
    //    $duplicateForm = null;
    //
    //    // If we're duplicating the form, swap $form with the duplicate
    //
    //    if ($duplicate) {
    //        $duplicateForm = FormsModule::getInstance()->forms->createNewForm(
    //            $request->getBodyParam('name'),
    //            $request->getBodyParam('handle')
    //        );
    //
    //        if ($duplicateForm !== null) {
    //            $form->id = $duplicateForm->getId();
    //            $form->setFieldLayoutId($duplicateForm->getFieldLayoutId());
    //            $form->uid = $duplicateForm->uid;
    //        } else {
    //            throw new Exception('Error creating Form');
    //        }
    //    }
    //
    //    $this->populateFormModel($form);
    //    $currentTitleFormat = $form->titleFormat;
    //    $this->prepareFieldLayout($form, $duplicate, $duplicateForm);
    //
    //    // Save it
    //    if (!FormsModule::getInstance()->forms->saveForm($form, $duplicate)) {
    //
    //        Craft::$app->getSession()->setError(Craft::t('sprout-module-forms', 'Couldn’t save form.'));
    //
    //        Craft::$app->getUrlManager()->setRouteParams([
    //            'form' => $form,
    //        ]);
    //
    //        return null;
    //    }

    //    if ($oldTitleFormat !== $currentTitleFormat) {
    //        FormsModule::getInstance()->submissions->resaveElements($form->getId());
    //    }
    //
    //    Craft::$app->getSession()->setNotice(Craft::t('sprout-module-forms', 'Form saved.'));
    //
    //    $_POST['redirect'] = str_replace('{id}', $form->getId(), $_POST['redirect']);
    //
    //    return $this->redirectToPostedUrl($form);
    //}

    public function actionCreateForm(): Response
    {
        $site = Cp::requestedSite();

        if (!$site instanceof Site) {
            throw new ForbiddenHttpException('User not authorized to edit content in any sites.');
        }

        if (!FormsModule::getInstance()->forms->canCreateForm()) {
            throw new WrongEditionException('Please upgrade to Sprout Forms Pro Edition to create unlimited forms.');
        }

        $form = Craft::createObject(FormElement::class);
        $name ??= 'Form';
        $handle ??= 'form';

        $settings = FormsModule::getInstance()->getSettings();

        // @todo - duplicate methods getFieldAsNew in service and field classes...
        $formsService = FormsModule::getInstance()->forms;

        $form->title = $name;
        $form->name = $formsService->getFieldAsNew('name', $name);
        $form->handle = $formsService->getFieldAsNew('handle', $handle);
        $form->titleFormat = "{dateCreated|date('D, d M Y H:i:s')}";

        $form->formTypeUid = Craft::$app->getRequest()->getRequiredParam('formTypeUid');
        $form->saveData = $settings->enableSaveData && $settings->enableSaveDataDefaultValue;
        $form->submissionMethod = $settings->defaultSubmissionMethod ?: 'sync';

        $user = Craft::$app->getUser()->getIdentity();

        if (!$form->canSave($user)) {
            throw new ForbiddenHttpException('User not authorized to save this report.');
        }

        // Create a Layout so we can build conditions and other stuff before we have fields in a layout
        $submissionLayoutRecord = new FieldLayoutRecord();
        $submissionLayoutRecord->type = SubmissionElement::class;
        $submissionLayoutRecord->uid = StringHelper::UUID();
        $submissionLayoutRecord->save();

        $form->submissionFieldLayoutId = $submissionLayoutRecord->id;

        $form->setScenario(Element::SCENARIO_ESSENTIALS);
        if (!Craft::$app->getDrafts()->saveElementAsDraft($form, Craft::$app->getUser()->getId(), null, null, false)) {
            throw new ServerErrorHttpException(sprintf('Unable to save report as a draft: %s', implode(', ', $form->getErrorSummary(true))));
        }

        return $this->redirect($form->getCpEditUrl());
    }

    public function actionGetSubmissionFieldLayout(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $formId = Craft::$app->getRequest()->getRequiredBodyParam('formId');

        $form = Craft::$app->getElements()->getElementById($formId, FormElement::class);

        if (!$form) {
            return $this->asJson([
                'success' => false,
                'errors' => 'Form not found.',
            ]);
        }

        $submissionFieldLayout = $form->getSubmissionFieldLayout();

        $uiTabs = [];
        $selectedTabId = null;

        if ($submissionFieldLayout) {
            $tabs = $submissionFieldLayout->getTabs();

            if (!$tabs) {
                $tabs = $form->getDefaultSubmissionTabs();
            }

            $firstTab = $tabs[0];
            $selectedTabId = $firstTab->id;

            foreach ($tabs as $tab) {

                $elements = [];

                $tabConfig = $tab->getConfig();
                $elements = $tabConfig['elements'] ?? [];
                foreach ($elements as $element) {

                    $field = Craft::$app->getFields()->getFieldByUid($element['fieldUid']);
                    if (!$field) {
                        $field = new MissingField();
                    }

                    $fieldData = FormBuilderHelper::getFieldUiSettings($field);

                    $elements[] = array_merge($element, [
                        'field' => $fieldData['field'],
                        'uiSettings' => $fieldData['uiSettings'],
                    ]);
                }

                $uiTabs[] = [
                    'id' => $tab->id,
                    'uid' => $tab->uid,
                    'name' => $tab->name,
                    'userCondition' => $tab->getUserCondition(),
                    'elementCondition' => $tab->getElementCondition(),
                    'elements' => $elements,
                ];
            }
        }

        return $this->asJson([
            'success' => true,
            'formId' => $formId,
            'fieldLayoutUid' => $submissionFieldLayout->uid,
            'tabs' => $uiTabs,
            'selectedTabId' => $selectedTabId,
        ]);
    }

    public function actionGetFormTabObject(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $name = Craft::$app->getRequest()->getRequiredBodyParam('name');
        $userCondition = Craft::$app->getRequest()->getRequiredBodyParam('userCondition');
        $elementCondition = Craft::$app->getRequest()->getRequiredBodyParam('elementCondition');

        return $this->asJson([
            'name' => $name,
            'userCondition' => $userCondition,
            'elementCondition' => $elementCondition,
        ]);
    }

    public function actionGetFormTabSettingsHtml(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $formId = Craft::$app->getRequest()->getRequiredBodyParam('formId');
        $form = Craft::$app->getElements()->getElementById($formId, FormElement::class);

        $tabSettings = Craft::$app->getRequest()->getRequiredBodyParam('tab');

        $fieldLayout = $form->getSubmissionFieldLayout();

        $tab = new FieldLayoutTab();
        $tab->setLayout($fieldLayout);

        $fieldLayout->setTabs([$tab]);

        $tab->name = $tabSettings['name'] ?? null;
        $tab->setUserCondition($tabSettings['userCondition']);
        $tab->setElementCondition($tabSettings['elementCondition']);

        $view = Craft::$app->getView();
        $view->startJsBuffer();
        $conditionBuilderHtml = $tab->getSettingsHtml();
        $conditionBuilderJs = $view->clearJsBuffer();

        return $this->asJson([
            'success' => true,
            'tabId' => $tabSettings['id'],
            'settingsHtml' => $conditionBuilderHtml,
            'conditionBuilderJs' => $conditionBuilderJs,
        ]);
    }

    public function actionGetFormFieldSettingsHtml(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $view = Craft::$app->getView();

        $currentUser = Craft::$app->getUser()->getIdentity();

        if (!$currentUser->can(FormsModule::p('editForms'))) {
            throw new ForbiddenHttpException('User is not authorized to perform this action.');
        }

        $fieldConfig = Craft::$app->getRequest()->getRequiredBodyParam('field');

        $class = $fieldConfig['type'] ?? null;
        $fieldSettings = $fieldConfig['settings'] ?? [];

        $field = new $class([
            'name' => $fieldConfig['name'],
            'handle' => $fieldConfig['handle'],
            'instructions' => $fieldConfig['instructions'],
            'required' => $fieldConfig['required'],
            //'elements' => $tab->getElementConfigs();
        ]);

        ///** @var FieldInterface $field */
        //$field = Craft::createObject($class, [
        //    'name' => $fieldConfig['name'],
        //    'handle' => $fieldConfig['handle'],
        //    'instructions' => $fieldConfig['instructions'],
        //    'required' => $fieldConfig['required'],
        //    'userCondition' => $fieldConfig['userCondition'],
        //    'elementCondition' => $fieldConfig['elementCondition'],
        //]);
        $field->setAttributes($fieldSettings, false);

        //$html = $field->getSlideoutSettingsHtml();

        $html = $view->renderTemplate('sprout-module-forms/forms/_formbuilder/editFormFieldSlideout', [
            'field' => $field,
            'settings' => [
                'userCondition' => Json::decodeIfJson($fieldConfig['userCondition']),
                'elementCondition' => Json::decodeIfJson($fieldConfig['elementCondition']),
            ],
        ]);

        return $this->asJson([
            'success' => true,
            'settingsHtml' => StringHelper::collapseWhitespace($html),
        ]);
    }

    public function actionGetFormFieldObject(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $required = Craft::$app->getRequest()->getRequiredBodyParam('required');
        $name = Craft::$app->getRequest()->getRequiredBodyParam('name');
        $instructions = Craft::$app->getRequest()->getRequiredBodyParam('instructions');
        $userCondition = Craft::$app->getRequest()->getRequiredBodyParam('userCondition');
        $elementCondition = Craft::$app->getRequest()->getRequiredBodyParam('elementCondition');
        $settings = Craft::$app->getRequest()->getRequiredBodyParam('settings');

        return $this->asJson([
            'required' => $required,
            'name' => $name,
            'instructions' => $instructions,
            'userCondition' => $userCondition,
            'elementCondition' => $elementCondition,
            'settings' => $settings,
        ]);
    }

    public function actionGetFormFieldSettingsData(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $fieldSettings = Craft::$app->getRequest()->getRequiredBodyParam('fieldSettings');

        // @todo - how to parse this crap..
        $multidimensionalStrings = [
            'settings[options]',
            'settings[options][0][label]',
            'settings[options][0][value]',
            'settings[options][0][default]',
        ];

        return $this->asJson([
            'success' => true,
            'fieldSettings' => $fieldSettings,
        ]);
    }

    public function actionDeleteForm(): Response
    {
        $this->requirePostRequest();
        $this->requirePermission(FormsModule::p('editForms'));

        $request = Craft::$app->getRequest();

        // Get the Form these fields are related to
        $formId = $request->getRequiredBodyParam('formId');
        $form = FormsModule::getInstance()->forms->getFormById($formId);

        if (!$form instanceof ElementInterface) {
            throw new NotFoundHttpException('Form not found');
        }

        FormsModule::getInstance()->forms->deleteForm($form);

        return $this->redirectToPostedUrl($form);
    }

    public function prepareFieldLayout(FormElement $form, bool $duplicate = false, $duplicatedForm = null): void
    {
        $this->requirePermission(FormsModule::p('editForms'));

        // Set the field layout
        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();

        if ($duplicate) {
            $fieldLayout = FormsModule::getInstance()->formFields->getDuplicateLayout($duplicatedForm, $fieldLayout);
        }

        // Make sure we have a layout if:
        // 1. Form fails validation due to no fields existing
        // 2. We are saving General Settings and no Layout exists
        //if ((is_countable($fieldLayout->getFields()) ? count($fieldLayout->getFields()) : 0) === 0) {
        //    $fieldLayout = $form->getFieldLayout();
        //}

        //$fieldLayout->type = FormElement::class;

        //$form->setFieldLayout($fieldLayout);

        // Delete any fields removed from the layout
        $deletedFields = Craft::$app->getRequest()->getBodyParam('deletedFields', []);

        if ((is_countable($deletedFields) ? count($deletedFields) : 0) > 0) {
            // Backup our field context and content table
            $oldFieldContext = Craft::$app->content->fieldContext;
            $oldContentTable = Craft::$app->content->contentTable;

            // Set our field content and content table to work with our form output
            Craft::$app->content->fieldContext = $form->getSubmissionFieldContext();
            Craft::$app->content->contentTable = $form->getSubmissionContentTable();

            $currentTitleFormat = null;

            foreach ($deletedFields as $fieldId) {
                // If a deleted field is used in the titleFormat setting, update it
                $currentTitleFormat = FormsModule::getInstance()->forms->cleanTitleFormat($fieldId);
                Craft::$app->fields->deleteFieldById($fieldId);
            }

            if ($currentTitleFormat) {
                // update the titleFormat
                $form->titleFormat = $currentTitleFormat;
            }

            // Reset our field context and content table to what they were previously
            Craft::$app->content->fieldContext = $oldFieldContext;
            Craft::$app->content->contentTable = $oldContentTable;
        }
    }

    /**
     * This action allows create a new Tab to current layout
     */
    //public function actionAddFormTab(): Response
    //{
    //    $this->requireAcceptsJson();
    //    $this->requirePermission(FormsModule::p('editForms'));
    //
    //    $request = Craft::$app->getRequest();
    //    $formId = $request->getBodyParam('formId');
    //
    //    // @todo - should we rename this to Title as it is stored in the DB?
    //    $name = $request->getBodyParam('name');
    //
    //    $tab = null;
    //
    //    if ($formId && $name) {
    //        $tab = FormsModule::getInstance()->formFields->createNewTab($formId, $name);
    //
    //        if ($tab->id) {
    //            return $this->asJson([
    //                'success' => true,
    //                'tab' => [
    //                    'id' => $tab->id,
    //                    'name' => $tab->name,
    //                ],
    //            ]);
    //        }
    //    }
    //
    //    return $this->asJson([
    //        'success' => false,
    //        'errors' => $tab->getErrors(),
    //    ]);
    //}

    //public function actionDeleteFormTab(): Response
    //{
    //    $this->requireAcceptsJson();
    //    $this->requirePermission(FormsModule::p('editForms'));
    //
    //    $request = Craft::$app->getRequest();
    //    $tabId = $request->getBodyParam('id');
    //    $tabId = str_replace('tab-', '', $tabId);
    //
    //    // @todo - requests the deleteAction method grabs all data attributes not just the ID
    //    $tabRecord = FieldLayoutTabRecord::findOne($tabId);
    //
    //    if ($tabRecord !== null) {
    //        /** @var FormElement $form */
    //        $form = FormElement::find()
    //            ->submissionFieldLayoutId($tabRecord->layoutId)
    //            ->one();
    //
    //        if (FormsModule::getInstance()->formFields->deleteTab($form, $tabRecord)) {
    //            return $this->asJson([
    //                'success' => true,
    //            ]);
    //        }
    //    }
    //
    //    return $this->asJson([
    //        'success' => false,
    //        'errors' => $tabRecord->getErrors(),
    //    ]);
    //}

    /**
     * This action allows rename a current Tab
     */
    //public function actionRenameFormTab(): Response
    //{
    //    $this->requireAcceptsJson();
    //    $this->requirePermission(FormsModule::p('editForms'));
    //
    //    $request = Craft::$app->getRequest();
    //    $tabId = $request->getBodyParam('tabId');
    //    $newName = $request->getBodyParam('newName');
    //
    //    if ($tabId && $newName) {
    //        $result = FormsModule::getInstance()->formFields->renameTab($tabId, $newName);
    //
    //        if ($result) {
    //            return $this->asJson([
    //                'success' => true,
    //            ]);
    //        }
    //    }
    //
    //    return $this->asJson([
    //        'success' => false,
    //        'errors' => Craft::t('sprout-module-forms', 'Unable to rename tab'),
    //    ]);
    //}

    //public function actionReorderFormTabs(): Response
    //{
    //    $this->requirePostRequest();
    //    $this->requireAcceptsJson();
    //    $this->requirePermission(FormsModule::p('editForms'));
    //
    //    $formTabIds = Json::decode(Craft::$app->getRequest()->getRequiredBodyParam('ids'));
    //
    //    $db = Craft::$app->getDb();
    //    /** @var Transaction $transaction */
    //    $transaction = $db->beginTransaction();
    //
    //    try {
    //        // Loop through our reordered IDs and update the DB with their new order
    //        // increment $index by one to avoid using '0' in the sort order
    //        foreach ($formTabIds as $index => $tabId) {
    //            $db->createCommand()->update(Table::FIELDLAYOUTTABS, [
    //                'sortOrder' => $index + 1,
    //            ], ['id' => $tabId], [], false)->execute();
    //        }
    //
    //        $transaction->commit();
    //
    //        return $this->asJson([
    //            'success' => true,
    //        ]);
    //    } catch (\yii\db\Exception) {
    //        $transaction->rollBack();
    //    }
    //
    //    return $this->asJson([
    //        'success' => false,
    //        'errors' => Craft::t('sprout-module-forms', 'Unable to rename tab'),
    //    ]);
    //}

    //public function actionGetUpdatedLayoutHtml(): Response
    //{
    //    $this->requirePostRequest();
    //    $this->requireAcceptsJson();
    //    $this->requirePermission(FormsModule::p('editForms'));
    //
    //    $formId = Craft::$app->getRequest()->getBodyParam('formId');
    //    $form = FormsModule::getInstance()->forms->getFormById($formId);
    //
    //    if (!$form instanceof ElementInterface) {
    //        throw new ElementNotFoundException('Form not found.');
    //    }
    //
    //    $view = Craft::$app->getView();
    //
    //    $tabs = FormsModule::getInstance()->forms->getTabsForFieldLayout($form);
    //
    //    $tabsHtml = empty($tabs) ? null : $view->renderTemplate('sprout-module-forms/forms/_includes/tabs', [
    //        'formTabs' => $tabs,
    //    ]);
    //
    //    $contentHtml = $view->renderTemplate('sprout-module-forms/forms/_editFormContent', [
    //        'form' => $form,
    //        'fieldLayout' => $form->getFieldLayout(),
    //    ]);
    //
    //    return $this->asJson([
    //        'success' => true,
    //        'tabsHtml' => $tabsHtml,
    //        'contentHtml' => $contentHtml,
    //        'headHtml' => $view->getHeadHtml(),
    //        'bodyHtml' => $view->getBodyHtml(),
    //    ]);
    //}

    //private function getFormModel(): FormElement
    //{
    //    $request = Craft::$app->getRequest();
    //    $formId = $request->getBodyParam('formId');
    //    $siteId = $request->getBodyParam('siteId');
    //
    //    if ($formId) {
    //        $form = FormsModule::getInstance()->forms->getFormById($formId, $siteId);
    //
    //        if (!$form instanceof ElementInterface) {
    //            throw new NotFoundHttpException('Form not found');
    //        }
    //
    //        // Set oldHandle to the value from the db so we can
    //        // determine if we need to rename the content table
    //        $form->oldHandle = $form->handle;
    //    } else {
    //        $form = new FormElement();
    //
    //        if ($siteId) {
    //            $form->siteId = $siteId;
    //        }
    //    }
    //
    //    return $form;
    //}

    //private function populateFormModel(FormElement $form): void
    //{
    //    $request = Craft::$app->getRequest();
    //
    //    // Set the form attributes, defaulting to the existing values for whatever is missing from the post data
    //    $form->name = $request->getBodyParam('name', $form->name);
    //    $form->handle = $request->getBodyParam('handle', $form->handle);
    //    $form->displaySectionTitles = $request->getBodyParam('displaySectionTitles', $form->displaySectionTitles);
    //    $form->redirectUri = $request->getBodyParam('redirectUri', $form->redirectUri);
    //    $form->saveData = $request->getBodyParam('saveData', $form->saveData);
    //    $form->submissionMethod = $request->getBodyParam('submissionMethod', $form->submissionMethod);
    //    $form->errorDisplayMethod = $request->getBodyParam('errorDisplayMethod', $form->errorDisplayMethod);
    //    $form->messageOnSuccess = $request->getBodyParam('messageOnSuccess', $form->messageOnSuccess);
    //    $form->messageOnError = $request->getBodyParam('messageOnError', $form->messageOnError);
    //    $form->submitButtonText = $request->getBodyParam('submitButtonText', $form->submitButtonText);
    //    $form->titleFormat = $request->getBodyParam('titleFormat', $form->titleFormat);
    //    $form->formTypeUid = $request->getBodyParam('formTypeUid', $form->formTypeUid);
    //    $form->enableCaptchas = $request->getBodyParam('enableCaptchas', $form->enableCaptchas);
    //
    //    if (!$form->titleFormat) {
    //        $form->titleFormat = "{dateCreated|date('D, d M Y H:i:s')}";
    //    }
    //
    //    if (!$form->displaySectionTitles) {
    //        $form->displaySectionTitles = false;
    //    }
    //
    //    if (!$form->saveData) {
    //        $form->saveData = false;
    //    }
    //
    //    if (!$form->enableCaptchas) {
    //        $form->enableCaptchas = false;
    //    }
    //
    //    if (!$form->submissionMethod) {
    //        $form->submissionMethod = 'sync';
    //    }
    //
    //    if (!$form->errorDisplayMethod) {
    //        $form->errorDisplayMethod = 'inline';
    //    }
    //}
}
