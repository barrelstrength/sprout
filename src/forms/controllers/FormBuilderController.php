<?php

namespace BarrelStrength\Sprout\forms\controllers;

use BarrelStrength\Sprout\forms\components\elements\FormElement;
use BarrelStrength\Sprout\forms\formfields\CustomFormField;
use BarrelStrength\Sprout\forms\formfields\FormFieldLayoutTab;
use BarrelStrength\Sprout\forms\FormsModule;
use Craft;
use craft\elements\conditions\users\UserCondition;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\Template;
use craft\web\Controller as BaseController;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class FormBuilderController extends BaseController
{
    public function actionGetSubmissionFieldLayout(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $formId = Craft::$app->getRequest()->getRequiredBodyParam('formId');

        /** @var FormElement $form */
        $form = Craft::$app->getElements()->getElementById($formId, FormElement::class);
        $layout = $form->getSubmissionFieldLayout();

        return $this->asJson([
            'success' => true,
            'formId' => $formId,
            'layout' => $layout->getFormBuilderConfig(),
        ]);
    }

    public function actionEditFormTabSlideoutViaCpScreen(): Response
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

        $tabConfig = Craft::$app->getRequest()->getRequiredParam('tab');

        $tab = new FormFieldLayoutTab();
        $tab->name = $tabConfig['name'] ?? Craft::t('sprout-module-forms', 'Page');

        $view = Craft::$app->getView();
        $view->startJsBuffer();

        $settingsHtml = $tab->getSettingsHtml();
        $tabSettingsJs = $view->clearJsBuffer();

        $html =
            Template::raw($settingsHtml) .
            Template::raw($tabSettingsJs);

        return $this->asCpScreen()
            ->submitButtonLabel('Apply')
            ->action('sprout-module-forms/form-builder/edit-form-tab-slideout-response')
            ->contentHtml($html);
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
        $layoutElementConfig = Json::decodeIfJson($layoutElementConfig);

        $fieldConfig = $layoutElementConfig['formField'];

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

        $fieldLayoutElement->required = $layoutElementConfig['required'] === true;
        $fieldLayoutElement->width = $layoutElementConfig['width'];
        $fieldLayoutElement->uid = $layoutElementConfig['uid'];

        // Remove CustomFormField::conditional() method when ready to implement conditional logic
        //$fieldLayoutElement->setUserCondition($layoutElementConfig['userCondition']);
        //$fieldLayoutElement->setElementCondition($layoutElementConfig['elementCondition']);

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

        //Craft::$app->getView()->registerAssetBundle(ConditionBuilderAsset::class);

        /** @var UserCondition $userCondition */
        //$userCondition = !empty($fieldLayoutElement->userCondition)
        //    ? Craft::$app->conditions->createCondition($fieldLayoutElement->userCondition)
        //    : Craft::createObject(UserCondition::class);
        //$userCondition->elementType = SubmissionElement::class;
        //$userCondition->sortable = true;
        //$userCondition->mainTag = 'div';
        //$userCondition->name = 'userConditionRules';
        //$userCondition->id = 'userConditionRules';

        //$conditionHtml = self::swapPlaceholders($userCondition->getBuilderHtml(), $layoutElementConfig['fieldUid']);
        //let settingsHtml = self.swapPlaceholders(response.data.settingsHtml, response.data.fieldUid);

        //$conditionHtml = Cp::fieldHtml($userCondition->getBuilderHtml(), [
        //    'label' => Craft::t('app', 'Current User Condition'),
        //    'instructions' => Craft::t('app', 'Only show for users who match the following rules:'),
        //]);

        // @featureRequest
        // Setting fieldUid throws an error if the field is just created in the layout
        // and isn't yet created in the DB, so we work around that by not setting it here
        //$fieldLayoutElement->fieldUid = $layoutElementConfig['fieldUid'];

        // merge old field and fieldLayoutElement['field'] prioritizing fieldLayoutElement['field']
        //$field = array_merge($oldField, $fieldLayoutElement['field']);

        //$field = FormBuilderHelper::getFieldData($layoutElementConfig['fieldUid']);
        //$field = $fieldLayoutElement['field'];

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
            'form-field-advanced' => [
                // FieldLayoutForm
                //'tabId' => 'form-field',
                'label' => Craft::t('sprout-module-forms', 'Advanced'),
                'url' => '#form-field-advanced',
                'visible' => false,
                //'class' => $tab->hasErrors ? 'error' : null,
            ],
            //'form-field-rules' => [
            //FieldLayoutForm
            //'tabId' => 'form-field-conditions',
            //'label' => Craft::t('sprout-module-forms', 'Field Rules'),
            //'url' => '#form-field-rules',
            //'visible' => false,
            //'class' => $tab->hasErrors ? 'error' : null,
            //],
        ];

        return $this->asCpScreen()
            ->tabs($tabs)
            ->submitButtonLabel('Apply')
            ->action('sprout-module-forms/form-builder/edit-form-field-slideout-response')
            ->contentTemplate('sprout-module-forms/forms/_formbuilder/editFormFieldContent.twig', [
                'field' => $field,
                'fieldLayoutElement' => $fieldLayoutElement,
                'fieldUid' => $layoutElementConfig['fieldUid'],
                'settingsHtml' => $settingsHtml,
                // Render conditions in the contentTemplate to ensure JS works
                //'userCondition' => $userCondition,
            ]);
    }

    public function actionEditFormTabSlideoutResponse(): Response
    {
        // get field from params
        $name = $this->request->getRequiredBodyParam('name');
        //$userCondition = $this->request->getRequiredBodyParam('userCondition');
        //$elementCondition = $this->request->getRequiredBodyParam('elementCondition');

        // Return params and let JS update field model in layout
        return $this->asJson([
            'success' => true,
            'message' => Craft::t('sprout-module-forms', 'Tab updated.'),
            'tab' => [
                'name' => $name,
                //'userCondition' => $userCondition,
                //'elementCondition' => $elementCondition,
            ],
            'params' => $this->request->getBodyParams(),
        ]);
    }

    public function actionEditFormFieldSlideoutResponse(): Response
    {
        $layoutElement = $this->request->getBodyParam('layoutElement');
        $layoutElement['required'] = $layoutElement['required'] === '1';

        // Return params and let JS update field model in layout
        return $this->asJson([
            'success' => true,
            'message' => Craft::t('sprout-module-forms', 'Field updated.'),
            'layoutElement' => $layoutElement,
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
}
