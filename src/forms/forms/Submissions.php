<?php

namespace BarrelStrength\Sprout\forms\forms;

use BarrelStrength\Sprout\core\jobs\PurgeElementHelper;
use BarrelStrength\Sprout\core\jobs\PurgeElements;
use BarrelStrength\Sprout\forms\captchas\Captcha;
use BarrelStrength\Sprout\forms\components\elements\FormElement;
use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use BarrelStrength\Sprout\forms\components\events\OnBeforeSaveSubmissionEvent;
use BarrelStrength\Sprout\forms\components\events\OnSaveSubmissionEvent;
use BarrelStrength\Sprout\forms\FormsModule;
use BarrelStrength\Sprout\forms\submissions\ResaveFormSubmissions;
use BarrelStrength\Sprout\forms\submissions\SubmissionRecord;
use BarrelStrength\Sprout\forms\submissions\SubmissionsSpamLogRecord;
use BarrelStrength\Sprout\forms\submissions\SubmissionStatus;
use Craft;
use craft\base\ElementInterface;
use craft\helpers\Json;
use yii\base\Component;
use yii\base\Exception;
use yii\db\Transaction;

class Submissions extends Component
{
    public const SPAM_DEFAULT_LIMIT = 500;

    public function __construct(protected $submissionRecord = null)
    {
        if ($this->submissionRecord === null) {
            $this->submissionRecord = SubmissionRecord::find();
        }

        parent::__construct($submissionRecord);
    }

    /**
     * Returns an active or new submission element
     */
    public function getSubmission(FormElement $form): SubmissionElement
    {
        if (isset(FormsModule::getInstance()->forms->activeSubmissions[$form->handle])) {
            return FormsModule::getInstance()->forms->activeSubmissions[$form->handle];
        }

        $submission = new SubmissionElement();
        $submission->formId = $form->getId();

        FormsModule::getInstance()->forms->activeSubmissions[$form->handle] = $submission;

        return $submission;
    }

    /**
     * Set an activeSubmission on the Forms Service
     *
     * One scenario this can be used is if you are allowing users
     * to edit Submissions on the front-end and need to make the
     * displayTab or displayField tags aware of the active submission
     * without calling the displayForm tag.
     */
    public function setSubmission(FormElement $form, SubmissionElement $submission): void
    {
        FormsModule::getInstance()->forms->activeSubmissions[$form->handle] = $submission;
    }

    /**
     * Returns a submission model if one is found in the database by id
     */
    public function getSubmissionById($submissionId, int $siteId = null): SubmissionElement|ElementInterface|null
    {
        $query = SubmissionElement::find();
        $query->id($submissionId);
        $query->siteId($siteId);

        // We are using custom statuses, so all are welcome
        $query->status(null);

        return $query->one();
    }

    public function saveSubmission(SubmissionElement $submission): bool
    {
        $isNewSubmission = !$submission->getId();

        if ($submission->getId()) {
            $submissionRecord = SubmissionRecord::findOne($submission->getId());

            if (!$submissionRecord instanceof SubmissionRecord) {
                throw new Exception('No submission exists with id ' . $submission->getId());
            }
        }

        $submission->validate();

        if ($submission->hasErrors()) {
            Craft::error($submission->getErrors(), __METHOD__);

            return false;
        }

        $event = new OnBeforeSaveSubmissionEvent([
            'submission' => $submission,
        ]);

        $this->trigger(SubmissionElement::EVENT_BEFORE_SAVE, $event);

        $db = Craft::$app->getDb();
        /** @var Transaction $transaction */
        $transaction = $db->beginTransaction();

        try {
            if (!$event->isValid || !empty($event->errors)) {
                foreach ($event->errors as $key => $error) {
                    $submission->addError($key, $error);
                }

                Craft::error('OnBeforeSaveSubmissionEvent is not valid', __METHOD__);

                return false;
            }

            $success = Craft::$app->getElements()->saveElement($submission);

            if (!$success) {
                Craft::error('Unable to save Submission.', __METHOD__);
                $transaction->rollBack();

                return false;
            }

            Craft::info('Submission Element Saved.', __METHOD__);

            $transaction->commit();

            $this->callOnSaveSubmissionEvent($submission, $isNewSubmission);
        } catch (\Exception $exception) {
            Craft::error('Failed to save element: ' . $exception->getMessage(), __METHOD__);
            $transaction->rollBack();

            throw $exception;
        }

        return true;
    }

    /**
     *
     * @return mixed
     */
    public function isSaveDataEnabled(FormElement $form, bool $isSpam = false): bool
    {
        $formType = $form->getFormType();
        $saveData = $formType->enableSaveData;

        if ($saveData) {
            // Allow Form to override global saveData setting
            $saveData = (bool)(int)$form->saveData;
        }

        // Let the SPAM setting determine if we save data if we are:
        // 1. Saving data globally and/or at the form level
        // 2. Processing a site request (if it's a CP request Submissions with spam status can always be updated)
        // 3. The submission being saved has been identified as spam
        if ($saveData &&
            Craft::$app->getRequest()->getIsSiteRequest() &&
            $isSpam
        ) {
            // If we have a spam submission, use the spam saveData setting
            $settings = FormsModule::getInstance()->getSettings();
            $saveData = $settings->saveSpamToDatabase;
        }

        return $saveData;
    }

    public function runPurgeSpamElements(bool $force = false): void
    {
        $settings = FormsModule::getInstance()->getSettings();

        $probability = $settings->cleanupProbability;

        // See Craft Garbage collection treatment of probability
        // https://docs.craftcms.com/v3/gc.html
        if (!$force && random_int(0, 1_000_000) >= $probability) {
            return;
        }

        // Default to 5000 if no integer is found in settings
        $spamLimit = is_int($settings->spamLimit)
            ? $settings->spamLimit
            : static::SPAM_DEFAULT_LIMIT;

        if ($spamLimit <= 0) {
            return;
        }

        $ids = SubmissionElement::find()
            ->limit(null)
            ->offset($spamLimit)
            ->status(SubmissionStatus::SPAM_STATUS_HANDLE)
            ->orderBy(['sprout_form_submissions.dateCreated' => SORT_DESC])
            ->ids();

        $purgeElements = new PurgeElements();
        $purgeElements->elementType = SubmissionElement::class;
        $purgeElements->idsToDelete = $ids;

        PurgeElementHelper::purgeElements($purgeElements);
    }

    public function logSubmissionsSpam(SubmissionElement $submission): bool
    {
        foreach ($submission->getCaptchas() as $captcha) {
            if ($captcha->hasErrors()) {
                $submissionsSpamLogRecord = new SubmissionsSpamLogRecord();
                $submissionsSpamLogRecord->submissionId = $submission->getId();
                $submissionsSpamLogRecord->type = $captcha::class;
                $submissionsSpamLogRecord->errors = Json::encode($captcha->getErrors(Captcha::CAPTCHA_ERRORS_KEY));
                $submissionsSpamLogRecord->save();
            }
        }

        return true;
    }

    public function callOnSaveSubmissionEvent($submission, $isNewSubmission): void
    {
        $event = new OnSaveSubmissionEvent([
            'submission' => $submission,
            'isNewSubmission' => $isNewSubmission,
        ]);

        $this->trigger(SubmissionElement::EVENT_AFTER_SAVE, $event);
    }

    public function resaveElements($formId): void
    {
        Craft::$app->getQueue()->push(new ResaveFormSubmissions([
            'formId' => $formId,
        ]));
    }
}
