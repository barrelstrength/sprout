<?php

namespace BarrelStrength\Sprout\forms\controllers;

use BarrelStrength\Sprout\forms\components\elements\FormElement;
use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use BarrelStrength\Sprout\forms\formfields\CustomFormField;
use BarrelStrength\Sprout\forms\forms\FormBuilderHelper;
use BarrelStrength\Sprout\forms\FormsModule;
use BarrelStrength\Sprout\forms\formtypes\FormTypeHelper;
use BarrelStrength\Sprout\forms\migrations\helpers\FormContentTableHelper;
use Craft;
use craft\base\Element;
use craft\elements\conditions\users\UserCondition;
use craft\errors\WrongEditionException;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\helpers\StringHelper;
use craft\models\FieldLayoutTab;
use craft\models\Site;
use craft\web\assets\conditionbuilder\ConditionBuilderAsset;
use craft\web\Controller as BaseController;
use yii\helpers\Json;
use yii\web\ForbiddenHttpException;
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

    //public function actionDuplicateForm()
    //{
    //    $this->requirePermission(FormsModule::p('editForms'));
    //
    //    return $this->runAction('save-form', ['duplicate' => true]);
    //}

    public function actionNewForm(): Response
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
            throw new ForbiddenHttpException('User not authorized to create a form.');
        }

        $formTypeUid = Craft::$app->getRequest()->getRequiredParam('formTypeUid');
        $formType = FormTypeHelper::getFormTypeByUid($formTypeUid);

        return $this->renderTemplate('sprout-module-forms/forms/_new', [
            'formType' => $formType,
        ]);
    }

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
            throw new ForbiddenHttpException('User not authorized to create a form.');
        }

        $form->name = '';
        $form->handle = '';
        $form->titleFormat = "{dateCreated|date('D, d M Y H:i:s')}";
        $form->name = Craft::$app->getRequest()->getRequiredParam('name');
        $form->handle = StringHelper::toHandle($form->name) . '_' . StringHelper::randomString(6);
        $form->formTypeUid = Craft::$app->getRequest()->getRequiredParam('formTypeUid');

        $form->setScenario(Element::SCENARIO_ESSENTIALS);
        if (!Craft::$app->getDrafts()->saveElementAsDraft($form, Craft::$app->getUser()->getId(), null, null, false)) {
            throw new ServerErrorHttpException(sprintf('Unable to save report as a draft: %s', implode(', ', $form->getErrorSummary(true))));
        }

        $contentTableName = FormContentTableHelper::getContentTable($form->id);
        FormContentTableHelper::createContentTable($contentTableName);

        return $this->redirect($form->getCpEditUrl());
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
        $tabSettingsJs = $view->clearJsBuffer();

        return $this->asJson([
            'success' => true,
            'tabUid' => $tabSettings['uid'],
            'settingsHtml' => $settingsHtml,
            'tabSettingsJs' => $tabSettingsJs,
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

        // @featureRequest
        // Setting fieldUid throws an error if the field is just created in the layout
        // and isn't yet created in the DB, so we work around that by not setting it here
        //$fieldLayoutElement->fieldUid = $layoutElementConfig['fieldUid'];

        $requiredSettingsHtml = $view->renderTemplate('sprout-module-forms/forms/_formbuilder/editFormFieldSlideout', [
            'fieldLayoutElement' => $fieldLayoutElement,
            'field' => $field,
        ]);

        $fieldSettingsJs = $view->clearJsBuffer();

        return $this->asJson([
            'success' => true,
            'fieldUid' => $layoutElementConfig['fieldUid'],
            'requiredSettingsHtml' => $requiredSettingsHtml,
            'settingsHtml' => $settingsHtml,
            'fieldSettingsJs' => $fieldSettingsJs,
        ]);
    }

    public function actionEditFormFieldSlideoutViaCpScreen(): Response
    {
        $this->requireAcceptsJson();

        $currentUser = Craft::$app->getUser()->getIdentity();

        if (!$currentUser->can(FormsModule::p('editForms'))) {
            throw new ForbiddenHttpException('User is not authorized to perform this action.');
        }

        $formId = Craft::$app->getRequest()->getRequiredParam('formId');
        $form = Craft::$app->getElements()->getElementById($formId, FormElement::class);

        if (!$form) {
            return $this->asJson([
                'success' => false,
                'errors' => 'Form not found.',
            ]);
        }

        $layoutElementConfig = Craft::$app->getRequest()->getRequiredParam('layoutElement');
        $layoutElementConfig = Json::decode($layoutElementConfig);
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

        // Render Field Settings
        // Render Condition Builders
        // Render JS for condition builders
        // we used to do this in the JS after the response but asCpScreen doesn't
        // allow this in the same way. So we have to try to do it in the buffer.

        $settingsHtml = $fieldLayoutElement->getSettingsHtml();

        // Just get the Field Settings, without Condition builder stuff
        $field = $fieldLayoutElement->getField();
        $settingsHtml = $field->getSettingsHtml();

        Craft::$app->getView()->registerAssetBundle(ConditionBuilderAsset::class);

        /** @var UserCondition $userCondition */
        $userCondition = !empty($fieldLayoutElement->userCondition)
            ? Craft::$app->conditions->createCondition($fieldLayoutElement->userCondition)
            : Craft::createObject(UserCondition::class);
        $userCondition->elementType = SubmissionElement::class;
        $userCondition->sortable = true;
        $userCondition->mainTag = 'div';
        $userCondition->name = 'userConditionRules';
        $userCondition->id = 'userConditionRules';

        $conditionHtml = self::swapPlaceholders($userCondition->getBuilderHtml(), $layoutElementConfig['fieldUid']);

        //let settingsHtml = self.swapPlaceholders(response.data.settingsHtml, response.data.fieldUid);

        // @featureRequest
        // Setting fieldUid throws an error if the field is just created in the layout
        // and isn't yet created in the DB, so we work around that by not setting it here
        //$fieldLayoutElement->fieldUid = $layoutElementConfig['fieldUid'];

        //$requiredSettingsHtml = $view->renderTemplate('sprout-module-forms/forms/_formbuilder/editFormFieldSlideout', [
        //    'fieldLayoutElement' => $fieldLayoutElement,
        //    'field' => $field,
        //]);

        $contentHtml = $view->renderTemplate('sprout-module-forms/forms/_formbuilder/editFormFieldContent.twig', [
            'field' => FormBuilderHelper::getFieldData($layoutElementConfig['fieldUid']),
            'fieldLayoutElement' => $fieldLayoutElement,
            'fieldUid' => $layoutElementConfig['fieldUid'],
            //'requiredSettingsHtml' => $requiredSettingsHtml,
            'settingsHtml' => $settingsHtml,
            'conditionHtml' => $conditionHtml,
        ]);

        $fieldSettingsJs = $view->clearJsBuffer();

        $tabs = [
            'form-field-general' => [
                // FieldLayoutForm
                //'tabId' => 'form-field',
                'label' => Craft::t('sprout-module-forms', 'Form Field'),
                'url' => '#form-field-general',
                'visible' => true,
                //'class' => $tab->hasErrors ? 'error' : null,
            ],
            'form-field-rules' => [
                // FieldLayoutForm
                //'tabId' => 'form-field-conditions',
                'label' => Craft::t('sprout-module-forms', 'Field Rules'),
                'url' => '#form-field-rules',
                'visible' => false,
                //'class' => $tab->hasErrors ? 'error' : null,
            ],
        ];

        return $this->asCpScreen()
            ->tabs($tabs)
            ->contentTemplate('sprout-module-forms/forms/_formbuilder/editFormFieldSlideoutCpScreen.twig', [
                'contentHtml' => $contentHtml,
                'fieldSettingsJs' => $fieldSettingsJs,
            ]);
    }

    public static function swapPlaceholders($str, $sourceKey): ?string
    {
        $random = (string)floor(random_int(0, 1) * 1000000);
        $defaultId = 'condition' . $random;

        //return str
        //    . replace(/__ID__ /g, defaultId)
        //    .replace(/__SOURCE_KEY__(?=-)/g, Craft . formatInputId('"' + sourceKey + '"'))
        //    .replace(/__SOURCE_KEY__ / g, sourceKey);
        $formatInputId = Html::id('"' . $sourceKey . '"');
        $str = str_replace('__ID__', $defaultId, $str);
        $str = preg_replace('/__SOURCE_KEY__(?=-)/', $formatInputId, $str);
        $str = str_replace('__SOURCE_KEY__', $sourceKey, $str);

        return $str;
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
