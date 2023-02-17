<?php

namespace BarrelStrength\Sprout\forms\forms;

use BarrelStrength\Sprout\forms\components\elements\db\SubmissionElementQuery;
use BarrelStrength\Sprout\forms\components\elements\FormElement;
use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use BarrelStrength\Sprout\forms\formfields\FormField;
use BarrelStrength\Sprout\forms\FormsModule;
use Craft;
use craft\base\ElementInterface;

class FormsVariable
{
    /**
     * Gets a specific form. If no form is found, returns null
     *
     *
     *
     */
    public function getFormById(int $id): ?ElementInterface
    {
        return FormsModule::getInstance()->forms->getFormById($id);
    }

    /**
     * Gets a specific form by handle. If no form is found, returns null
     *
     *
     *
     */
    public function getForm(string $formHandle): ?ElementInterface
    {
        return FormsModule::getInstance()->forms->getFormByHandle($formHandle);
    }

    /**
     * Get all forms
     */
    public function getAllForms(): array
    {
        return FormsModule::getInstance()->forms->getAllForms();
    }

    public function getSubmissionById($id): ?ElementInterface
    {
        return FormsModule::getInstance()->submissions->getSubmissionById($id);
    }

    /**
     * Returns an active or new submission model
     *
     *
     * @return mixed
     */
    public function getSubmission(FormElement $form): SubmissionElement
    {
        return FormsModule::getInstance()->submissions->getSubmission($form);
    }

    /**
     * Set an active submission for use in your Form Templates
     *
     * See the Entries service setSubmission method for more details.
     */
    public function setSubmission(FormElement $form, SubmissionElement $submission): void
    {
        FormsModule::getInstance()->submissions->setSubmission($form, $submission);
    }

    /**
     * Gets last Submission and cleans up lastSubmissionId from session
     */
    public function getLastSubmission($formId = null): ?ElementInterface
    {
        if ($submissionId = Craft::$app->getSession()->get('lastSubmissionId')) {
            $submission = FormsModule::getInstance()->submissions->getSubmissionById($submissionId);

            if (!$submission instanceof ElementInterface) {
                return null;
            }

            if (!$formId || $formId === $submission->getForm()->id) {
                Craft::$app->getSession()->remove('lastSubmissionId');
            }
        }

        return $submission ?? null;
    }

    public function multiStepForm($settings): void
    {
        $currentStep = $settings['currentStep'] ?? null;
        $totalSteps = $settings['totalSteps'] ?? null;

        if (!$currentStep || !$totalSteps) {
            return;
        }

        if ($currentStep == 1) {
            // Make sure we are starting from scratch
            Craft::$app->getSession()->remove('multiStepForm');
            Craft::$app->getSession()->remove('multiStepFormSubmissionId');
            Craft::$app->getSession()->remove('currentStep');
            Craft::$app->getSession()->remove('totalSteps');
        }

        Craft::$app->getSession()->set('multiStepForm', true);
        Craft::$app->getSession()->set('currentStep', $currentStep);
        Craft::$app->getSession()->set('totalSteps', $totalSteps);
    }

    public function addFieldVariables(array $variables): void
    {
        Forms::addFieldVariables($variables);
    }

    /**
     * Returns a new SubmissionElementQuery instance.
     */
    public function entries($criteria = null): SubmissionElementQuery
    {
        $query = SubmissionElement::find();
        if ($criteria) {
            Craft::configure($query, $criteria);
        }

        return $query;
    }
}

