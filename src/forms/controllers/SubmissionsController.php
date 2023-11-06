<?php

namespace BarrelStrength\Sprout\forms\controllers;

use BarrelStrength\Sprout\forms\components\elements\FormElement;
use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use BarrelStrength\Sprout\forms\components\events\OnBeforePopulateSubmissionEvent;
use BarrelStrength\Sprout\forms\components\events\OnBeforeValidateSubmissionEvent;
use BarrelStrength\Sprout\forms\forms\SubmissionMethod;
use BarrelStrength\Sprout\forms\FormsModule;
use BarrelStrength\Sprout\forms\FormsSettings;
use Craft;
use craft\base\ElementInterface;
use craft\helpers\UrlHelper;
use craft\web\Controller as BaseController;
use yii\base\Exception;
use yii\helpers\Markdown;
use yii\web\Response;

class SubmissionsController extends BaseController
{
    public const EVENT_BEFORE_POPULATE = 'onBeforePopulate';

    public const EVENT_BEFORE_VALIDATE = 'onBeforeValidate';

    public ?FormElement $form = null;

    /**
     * Allows anonymous execution
     */
    protected int|bool|array $allowAnonymous = [
        'save-submission',
    ];

    public function init(): void
    {
        parent::init();

        $response = Craft::$app->getResponse();
        $headers = $response->getHeaders();
        $headers->set('Cache-Control', 'private');
    }

    public function actionSubmissionsIndexTemplate(): Response
    {
        $settings = FormsModule::getInstance()->getSettings();

        if (!$settings->enableSaveData) {
            return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('sprout/forms'));
        }

        $config = FormsModule::getInstance()->getSettings();

        return $this->renderTemplate('sprout-module-forms/submissions/index', [
            'title' => SubmissionElement::pluralDisplayName(),
            'elementType' => SubmissionElement::class,
            'selectedSubnavItem' => 'submissions',
        ]);
    }

    /**
     * Processes form submissions
     */
    public function actionSaveSubmission(): ?Response
    {
        $this->requirePostRequest();

        if (!FormsModule::isEnabled()) {
            throw new Exception('Form module not enabled');
        }

        $request = Craft::$app->getRequest();

        if ($request->getIsCpRequest()) {
            $this->requirePermission(FormsModule::p('editSubmissions'));
        }

        $formHandle = $request->getRequiredBodyParam('handle');
        $this->form = $this->form == null ? FormsModule::getInstance()->forms->getFormByHandle($formHandle) : $this->form;

        if (!$this->form instanceof ElementInterface) {
            throw new Exception('No form exists with the handle ' . $formHandle);
        }

        $event = new OnBeforePopulateSubmissionEvent([
            'form' => $this->form,
        ]);

        $this->trigger(self::EVENT_BEFORE_POPULATE, $event);

        $submission = $this->getSubmissionModel();
        $submission->formId = $this->form->id;

        $fieldsLocation = $this->request->getParam('fieldsLocation', 'fields');
        $submission->setFieldValuesFromRequest($fieldsLocation);

        $this->addHiddenValuesBasedOnFieldRules($submission);

        // Populate the submission with post data
        $this->populateSubmissionModel($submission);

        $statusId = $request->getBodyParam('statusId');
        $submissionStatus = FormsModule::getInstance()->submissionStatuses->getDefaultSubmissionStatus();
        $submission->statusId = $statusId ?? $submission->statusId ?? $submissionStatus->id;

        // Render the Submission Title
        try {
            $submission->title = Craft::$app->getView()->renderObjectTemplate($this->form->titleFormat, $submission);
        } catch (\Exception $exception) {
            Craft::error('Title format error: ' . $exception->getMessage(), __METHOD__);
        }

        $event = new OnBeforeValidateSubmissionEvent([
            'form' => $this->form,
            'submission' => $submission,
        ]);

        // Captchas are processed and added to
        $this->trigger(self::EVENT_BEFORE_VALIDATE, $event);

        $submission->validate(null, false);

        // Allow override of redirect URL on failure
        if (Craft::$app->getRequest()->getBodyParam('redirectOnFailure') !== '') {
            $_POST['redirect'] = Craft::$app->getRequest()->getBodyParam('redirectOnFailure');
        }

        if ($submission->hasErrors()) {
            // Redirect back to form with validation errors
            return $this->redirectWithValidationErrors($submission);
        }

        // If we don't have errors or SPAM
        $success = true;

        if ($submission->hasCaptchaErrors()) {
            $submission->statusId = FormsModule::getInstance()->submissionStatuses->getSpamStatusId();
        }

        $saveData = FormsModule::getInstance()->submissions->isSaveDataEnabled($this->form, $submission->getIsSpam());

        // Save Data and Trigger the onSaveSubmissionEvent
        // This saves both valid and spam submissions
        // Integrations run on SubmissionElement::EVENT_AFTER_SAVE Event
        if ($saveData) {
            $success = FormsModule::getInstance()->submissions->saveSubmission($submission);

            if ($submission->hasCaptchaErrors()) {
                FormsModule::getInstance()->submissions->logSubmissionsSpam($submission);
            }
        } else {
            $isNewSubmission = !$submission->getId();
            FormsModule::getInstance()->submissions->callOnSaveSubmissionEvent($submission, $isNewSubmission);
        }

        FormsModule::getInstance()->submissions->runPurgeSpamElements();

        $settings = FormsModule::getInstance()->getSettings();

        if (!$success || $this->isSpamAndHasRedirectBehavior($submission, $settings)) {
            return $this->redirectWithValidationErrors($submission);
        }

        if ($this->form->submissionMethod === SubmissionMethod::SYNC) {
            $this->createLastSubmissionId($submission);
        }

        $successMessageTemplate = $submission->getForm()->messageOnSuccess ?? '';
        $successMessage = Craft::$app->getView()->renderObjectTemplate($successMessageTemplate, $submission);

        if (Craft::$app->getRequest()->getAcceptsJson()) {
            return $this->asJson([
                'success' => true,
                'message' => Markdown::process($successMessage),
            ]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('sprout-module-forms', 'Submission saved.'));

        return $this->redirectToPostedUrl($submission);
    }

    /**
     * Removes field values from POST request if a Field Rule defines a given field to hidden
     */
    private function addHiddenValuesBasedOnFieldRules(SubmissionElement $submission): bool
    {
        $postFields = $_POST['fields'] ?? [];
        $postFieldHandles = array_keys($postFields);
        $formFields = $this->form->getFields();
        $hiddenFields = [];

        foreach ($formFields as $formField) {
            if (!in_array($formField->handle, $postFieldHandles, true)) {
                $hiddenFields[] = $formField->handle;
            }
        }

        $submission->setHiddenFields($hiddenFields);

        return true;
    }

    /**
     * Populate a SubmissionElement with post data
     *
     * @access private
     */
    private function populateSubmissionModel(SubmissionElement $submission): void
    {
        $settings = FormsModule::getInstance()->getSettings();

        $request = Craft::$app->getRequest();

        // Our SubmissionElement requires that we assign it a FormElement id
        $submission->formId = $this->form->getId();
        $submission->ipAddress = $settings->trackRemoteIp ? $request->getRemoteIP() : null;
        $submission->referrer = $request->getReferrer();
        $submission->userAgent = $request->getUserAgent();

        // Set the submission attributes, defaulting to the existing values for whatever is missing from the post data
        $fieldsLocation = $request->getBodyParam('fieldsLocation', 'fields');
        $submission->setFieldValuesFromRequest($fieldsLocation);
        $submission->setFieldParamNamespace($fieldsLocation);
    }

    /**
     * Fetch or create a SubmissionElement class
     */
    private function getSubmissionModel(): SubmissionElement|ElementInterface
    {
        $submissionId = null;
        $request = Craft::$app->getRequest();

        $settings = FormsModule::getInstance()->getSettings();

        if ($request->getIsCpRequest() || $settings->enableEditSubmissionViaFrontEnd) {
            $submissionId = $request->getBodyParam('submissionId');
        }

        if (!$submissionId) {
            return new SubmissionElement();
        }

        $submission = FormsModule::getInstance()->submissions->getSubmissionById($submissionId);

        if (!$submission instanceof ElementInterface) {
            $message = Craft::t('sprout-module-forms', 'No submission exists with the given ID: {id}', [
                'submissionId' => $submissionId,
            ]);
            throw new Exception($message);
        }

        return $submission;
    }

    private function redirectWithValidationErrors(SubmissionElement $submission): ?Response
    {
        Craft::error($submission->getErrors(), __METHOD__);

        // Handle CP requests in a CP-friendly way
        if (Craft::$app->getRequest()->getIsCpRequest()) {
            Craft::$app->getSession()->setError(Craft::t('sprout-module-forms', 'Couldn’t save submission.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'submission' => $submission,
            ]);

            return null;
        }

        // Respond to ajax requests with JSON
        if (Craft::$app->getRequest()->getAcceptsJson()) {
            $errorMessageTemplate = $submission->getForm()->messageOnError ?? '';
            $errorMessage = Craft::$app->getView()->renderObjectTemplate($errorMessageTemplate, $submission);

            return $this->asJson([
                'success' => false,
                'errorDisplayMethod' => $submission->getForm()->errorDisplayMethod,
                'message' => Markdown::process($errorMessage),
                'errors' => $submission->getErrors(),
            ]);
        }

        // Front-end Requests need to be a bit more dynamic

        // Store this Submission Model in a variable in our Service layer so that
        // we can access the error object from our displayForm() variable
        FormsModule::getInstance()->forms->activeSubmissions[$this->form->handle] = $submission;

        // Return the form using it's name as a variable on the front-end
        Craft::$app->getUrlManager()->setRouteParams([
            $this->form->handle => $submission,
        ]);

        return null;
    }

    private function isSpamAndHasRedirectBehavior(SubmissionElement $submission, FormsSettings $settings): bool
    {
        if (!$submission->hasCaptchaErrors()) {
            return false;
        }

        return $settings->spamRedirectBehavior !== FormsSettings::SPAM_REDIRECT_BEHAVIOR_NORMAL;
    }

    private function createLastSubmissionId($submission): void
    {
        if (!Craft::$app->getRequest()->getIsCpRequest()) {
            // Store our new submission so we can recreate the Submission object on our thank you page
            Craft::$app->getSession()->set('lastSubmissionId', $submission->id);
        }
    }
}
