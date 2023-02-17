<?php

namespace BarrelStrength\Sprout\forms\components\elements\actions;

use BarrelStrength\Sprout\forms\FormsModule;
use BarrelStrength\Sprout\forms\submissions\SubmissionStatus;
use Craft;
use craft\base\ElementAction;
use craft\elements\db\ElementQueryInterface;

class MarkAsDefaultStatus extends ElementAction
{
    /**
     * @var SubmissionStatus
     */
    public SubmissionStatus $submissionStatus;

    public function init(): void
    {
        parent::init();

        $this->submissionStatus = FormsModule::getInstance()->submissionStatuses->getDefaultSubmissionStatus();
    }

    public function getTriggerLabel(): string
    {
        return Craft::t('sprout-module-forms', 'Mark as ' . $this->submissionStatus->name);
    }

    public function getConfirmationMessage(): ?string
    {
        return Craft::t('sprout-module-forms', 'Are you sure you want to mark the selected submissions as {statusName}', [
            'statusName' => $this->submissionStatus->name,
        ]);
    }

    public function performAction(ElementQueryInterface $query): bool
    {
        $response = FormsModule::getInstance()->submissionStatuses->markAsDefaultStatus($query->all());

        if (!$response) {
            $this->setMessage(Craft::t('sprout-module-forms', 'Unable to mark submissions as {statusName}.', [
                'statusName' => $this->submissionStatus->name,
            ]));

            return false;
        }

        $this->setMessage(Craft::t('sprout-module-forms', 'Submissions marked as {statusName}.', [
            'statusName' => $this->submissionStatus->name,
        ]));

        return true;
    }
}
