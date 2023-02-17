<?php

namespace BarrelStrength\Sprout\forms\components\notificationevents;

use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use BarrelStrength\Sprout\forms\components\events\OnSaveSubmissionEvent;
use BarrelStrength\Sprout\forms\forms\Submissions;
use BarrelStrength\Sprout\forms\FormsModule;
use BarrelStrength\Sprout\transactional\notificationevents\NotificationEvent;
use Craft;
use craft\base\ElementInterface;
use craft\events\ElementEvent;
use craft\events\ModelEvent;

class SaveSubmissionNotificationEvent extends NotificationEvent
{
    public bool $whenNew = false;

    public bool $whenUpdated = false;

    public array $availableForms = [];

    public array $formIds = [];

    public static function getEventClassName(): ?string
    {
        return Submissions::class;
    }

    public static function getEventName(): ?string
    {
        return SubmissionElement::EVENT_AFTER_SAVE;
    }

    public function getEventHandlerClassName(): ?string
    {
        return ModelEvent::class;
    }

    public static function displayName(): string
    {
        return Craft::t('sprout-module-forms', 'When a submission is saved (Sprout)');
    }

    public function getSettingsHtml($context = []): ?string
    {
        if (!$this->availableForms) {
            $this->availableForms = $this->getAllForms();
        }

        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/events/SaveSubmissionEvent/settings', [
            'event' => $this,
        ]);
    }

    public function getEventObject(): ?ElementInterface
    {
        /** @var ElementEvent $event */
        $event = $this->event ?? null;

        return $event->element ?? null;
    }

    /**
     * @todo fix bug where incorrect form can be selected.
     */
    public function getMockEventObject()
    {
        $criteria = SubmissionElement::find();
        $criteria->orderBy(['id' => SORT_DESC]);

        if (!empty($this->formIds)) {

            $formId = count($this->formIds) == 1 ? $this->formIds[0] : array_shift($this->formIds);

            $criteria->formId = $formId;
        }

        $submission = $criteria->one();

        if ($submission) {
            return $submission;
        }

        Craft::warning('Unable to generate a mock form Submission. Make sure you have at least one Submission submitted in your database.', __METHOD__);

        return null;
    }

    public function validateWhenTriggers(): void
    {
        /**
         * @var ElementEvent $event
         */
        $event = $this->event ?? null;

        $isNewSubmission = $event->isNewSubmission ?? false;

        $matchesWhenNew = $this->whenNew && $isNewSubmission ?? false;
        $matchesWhenUpdated = $this->whenUpdated && !$isNewSubmission ?? false;

        if (!$matchesWhenNew && !$matchesWhenUpdated) {
            $this->addError('event', Craft::t('sprout-module-forms', 'When a submission is saved Event does not match any scenarios.'));
        }

        // Make sure new submissions are new.
        if (($this->whenNew && !$isNewSubmission) && !$this->whenUpdated) {
            $this->addError('event', Craft::t('sprout-module-forms', '"When a submission is created" is selected but the submission is being updated.'));
        }

        // Make sure updated submissions are not new
        if (($this->whenUpdated && $isNewSubmission) && !$this->whenNew) {
            $this->addError('event', Craft::t('sprout-module-forms', '"When a submission is updated" is selected but the submission is new.'));
        }
    }

    public function validateEvent(): void
    {
        /** @var OnSaveSubmissionEvent $event */
        $event = $this->event ?? null;

        if (!$event) {
            $this->addError('event', Craft::t('sprout-module-forms', 'ElementEvent does not exist.'));
        }

        if (!$event->submission instanceof SubmissionElement) {
            $this->addError('event', Craft::t('sprout-module-forms', 'Event Element does not match class: {className}', [
                'className' => SubmissionElement::class,
            ]));
        }
    }

    public function validateCaptchas(): void
    {
        $submission = $this->event->submission;

        if ($submission->hasCaptchaErrors()) {
            $this->addError('event', Craft::t('sprout-module-forms', 'Submission has captcha errors.'));
        }
    }

    public function validateFormIds(): void
    {
        /** @var OnSaveSubmissionEvent $event */
        $event = $this->event ?? null;

        $elementId = null;

        if ($event->submission instanceof SubmissionElement) {

            $form = $event->submission->getForm();
            $elementId = $form->getId();
        }

        // If any section ids were checked, make sure the Submission belongs in one of them
        if (!in_array($elementId, $this->formIds, false)) {
            $this->addError('event', Craft::t('sprout-module-forms', 'The Form associated with the saved Submission Element does not match any selected Forms.'));
        }
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [
            'whenNew', 'required', 'when' => function(): bool {
                return $this->whenUpdated == false;
            },
        ];
        $rules[] = [
            'whenUpdated', 'required', 'when' => function(): bool {
                return $this->whenNew == false;
            },
        ];
        $rules[] = [['whenNew', 'whenUpdated'], 'validateWhenTriggers'];
        $rules[] = [['event'], 'validateEvent'];
        $rules[] = [['event'], 'validateCaptchas'];
        $rules[] = [['formIds'], 'validateFormIds'];

        return $rules;
    }

    /**
     * Returns an array of forms suitable for use in checkbox field
     */
    protected function getAllForms(): array
    {
        $forms = FormsModule::getInstance()->forms->getAllForms();
        $options = [];

        foreach ($forms as $form) {
            $options[] = [
                'label' => $form->name,
                'value' => $form->getId(),
            ];
        }

        return $options;
    }
}
