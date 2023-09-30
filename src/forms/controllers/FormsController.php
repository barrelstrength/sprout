<?php

namespace BarrelStrength\Sprout\forms\controllers;

use BarrelStrength\Sprout\forms\components\elements\FormElement;
use BarrelStrength\Sprout\forms\FormsModule;
use BarrelStrength\Sprout\forms\formtypes\FormTypeHelper;
use BarrelStrength\Sprout\forms\migrations\helpers\FormContentTableHelper;
use BarrelStrength\Sprout\forms\submissions\CustomFormField;
use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\errors\WrongEditionException;
use craft\fieldlayoutelements\CustomField;
use craft\helpers\Cp;
use craft\helpers\StringHelper;
use craft\models\FieldLayoutTab;
use craft\models\Site;
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

        $user = Craft::$app->getUser()->getIdentity();
        $form = Craft::createObject(FormElement::class);

        if (!$form->canSave($user)) {
            throw new ForbiddenHttpException('User not authorized to save this report.');
        }

        $form->name = '';
        $form->handle = '';
        $form->titleFormat = "{dateCreated|date('D, d M Y H:i:s')}";
        $form->formTypeUid = Craft::$app->getRequest()->getRequiredParam('formTypeUid');

        $form->setScenario(Element::SCENARIO_ESSENTIALS);
        if (!Craft::$app->getDrafts()->saveElementAsDraft($form, Craft::$app->getUser()->getId(), null, null, false)) {
            throw new ServerErrorHttpException(sprintf('Unable to save report as a draft: %s', implode(', ', $form->getErrorSummary(true))));
        }

        $contentTableName = FormContentTableHelper::getContentTable($form->id);
        FormContentTableHelper::createContentTable($contentTableName);

        $totalTabs = count($form->getFieldLayout()?->getTabs());
        $settingsTabAnchor = '#tab0' . $totalTabs . '--settings';

        return $this->redirect($form->getCpEditUrl() . $settingsTabAnchor);
    }

    public function actionGetSubmissionFieldLayout(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $formId = Craft::$app->getRequest()->getRequiredBodyParam('formId');

        /** @var FormElement $form */
        $form = Craft::$app->getElements()->getElementById($formId, FormElement::class);
        $layout = $form->getFormBuilderSubmissionFieldLayout();

        return $this->asJson([
            'success' => true,
            'formId' => $formId,
            'layout' => $layout,
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

        if (!$form) {
            return $this->asJson([
                'success' => false,
                'errors' => 'Form not found.',
            ]);
        }

        $tabSettings = Craft::$app->getRequest()->getRequiredBodyParam('tab');

        $fieldLayout = $form->getSubmissionFieldLayout();

        $tab = new FieldLayoutTab();
        $tab->setLayout($fieldLayout);

        $tab->name = $tabSettings['name'] ?? null;
        $tab->setUserCondition($tabSettings['userCondition']);
        $tab->setElementCondition($tabSettings['elementCondition']);
        $tab->uid = $tabSettings['uid'];

        $fieldLayout->setTabs([$tab]);

        $view = Craft::$app->getView();
        $view->startJsBuffer();
        $settingsHtml = $tab->getSettingsHtml();
        $conditionBuilderJs = $view->clearJsBuffer();

        return $this->asJson([
            'success' => true,
            'tabUid' => $tabSettings['uid'],
            'settingsHtml' => $settingsHtml,
            'conditionBuilderJs' => $conditionBuilderJs,
        ]);
    }

    public function actionGetFormFieldSettingsHtml(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $currentUser = Craft::$app->getUser()->getIdentity();

        if (!$currentUser->can(FormsModule::p('editForms'))) {
            throw new ForbiddenHttpException('User is not authorized to perform this action.');
        }

        $formId = Craft::$app->getRequest()->getRequiredBodyParam('formId');
        $form = Craft::$app->getElements()->getElementById($formId, FormElement::class);

        if (!$form) {
            return $this->asJson([
                'success' => false,
                'errors' => 'Form not found.',
            ]);
        }

        $layoutElementConfig = Craft::$app->getRequest()->getRequiredBodyParam('layoutElement');
        $fieldConfig = $layoutElementConfig['field'];

        $class = $fieldConfig['type'] ?? null;
        $fieldSettings = $fieldConfig['settings'] ?? [];

        unset(
            $fieldConfig['type'],
            $fieldConfig['tabUid'],
            $fieldConfig['settings'],
        );

        $field = new $class($fieldConfig);
        $field->setAttributes($fieldSettings, false);

        $fieldLayoutElement = new CustomFormField($field);
        $fieldLayoutElement->layout = $form->getSubmissionFieldLayout();

        $fieldLayoutElement->required = $layoutElementConfig['required'];
        $fieldLayoutElement->width = $layoutElementConfig['width'];
        $fieldLayoutElement->uid = $layoutElementConfig['uid'];

        $fieldLayoutElement->setUserCondition($layoutElementConfig['userCondition']);
        $fieldLayoutElement->setElementCondition($layoutElementConfig['elementCondition']);

        $view = Craft::$app->getView();
        $view->startJsBuffer();
        $settingsHtml = $fieldLayoutElement->getSettingsHtml();
        $conditionBuilderJs = $view->clearJsBuffer();

        // Setting fieldUid throws an if the field isn't created in the DB yet, so we work around that
        //$fieldLayoutElement->fieldUid = $layoutElementConfig['fieldUid'];
        //\Craft::dd($settingsHtml);
        $requiredSettingsHtml = $view->renderTemplate('sprout-module-forms/forms/_formbuilder/editFormFieldSlideout', [
            'fieldLayoutElement' => $fieldLayoutElement,
            'field' => $field,
            //'settingsHtml' => $settingsHtml,
            //'conditionBuilderJs' => $conditionBuilderJs,
        ]);

        $fieldSettingsHtml = $view->renderTemplate('sprout-module-forms/forms/_formbuilder/editFormFieldSettings', [
            'field' => $field,
        ]);

        return $this->asJson([
            'success' => true,
            'fieldUid' => $layoutElementConfig['fieldUid'],
            //'settingsHtml' => StringHelper::collapseWhitespace($html),
            'requiredSettingsHtml' => $requiredSettingsHtml,
            'additionalSettingsHtml' => $fieldSettingsHtml,
            'settingsHtml' => $settingsHtml,
            'conditionBuilderJs' => $conditionBuilderJs,
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

        Craft::$app->getElements()->deleteElementById($formId, FormElement::class);

        return $this->redirectToPostedUrl();
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
