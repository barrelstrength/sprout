<?php

namespace BarrelStrength\Sprout\forms\components\notificationevents;

use BarrelStrength\Sprout\forms\components\elements\conditions\SubmissionCondition;
use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use BarrelStrength\Sprout\forms\components\events\OnSaveSubmissionEvent;
use BarrelStrength\Sprout\forms\forms\Submissions;
use BarrelStrength\Sprout\transactional\notificationevents\BaseElementNotificationEvent;
use Craft;
use yii\base\Event;

/**
 * @property OnSaveSubmissionEvent $event
 */
class SaveSubmissionNotificationEvent extends BaseElementNotificationEvent
{
    public static function displayName(): string
    {
        return Craft::t('sprout-module-forms', 'When a form submission is saved (Sprout)');
    }

    public static function conditionType(): string
    {
        return SubmissionCondition::class;
    }

    public static function elementType(): string
    {
        return SubmissionElement::class;
    }

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
        return OnSaveSubmissionEvent::class;
    }

    public function getTipHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/notificationevents/submission-event-info.md');
    }

    public function getEventVariables(): array
    {
        return [
            'submission' => $this->event->submission,
        ];
    }

    /**
     * @todo fix bug where incorrect form can be selected.
     */
    public function getMockEventVariables(): array
    {
        $criteria = SubmissionElement::find();
        $criteria->orderBy(['id' => SORT_DESC]);

        if (!empty($this->formIds)) {
            $formId = count($this->formIds) == 1 ? $this->formIds[0] : array_shift($this->formIds);

            $criteria->formId = $formId;
        }

        $submission = $criteria->one();

        if (!$submission) {
            Craft::warning('Unable to generate a mock form Submission. Make sure you have at least one Submission submitted in your database.', __METHOD__);

            return [
                'submission' => null,
            ];
        }

        return [
            'submission' => $submission,
        ];
    }

    public function matchNotificationEvent(Event $event): bool
    {
        if (!$event instanceof OnSaveSubmissionEvent) {
            return false;
        }

        $element = $event->submission;

        if (!$event->isNewSubmission) {
            return false;
        }

        if ($element->hasCaptchaErrors()) {
            return false;
        }

        return $this->matchElement($element);
    }
}
