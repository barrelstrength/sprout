<?php

namespace BarrelStrength\Sprout\forms\components\elements\actions;

use BarrelStrength\Sprout\forms\FormsModule;
use Craft;
use craft\base\ElementAction;
use craft\elements\db\ElementQueryInterface;

class MarkAsSpam extends ElementAction
{
    public function getTriggerLabel(): string
    {
        return Craft::t('sprout-module-forms', 'Mark as Spam');
    }

    public function getConfirmationMessage(): ?string
    {
        return Craft::t('sprout-module-forms', 'Are you sure you want to mark the selected submissions as Spam?');
    }

    public function performAction(ElementQueryInterface $query): bool
    {
        $response = FormsModule::getInstance()->submissionStatuses->markAsSpam($query->all());

        if (!$response) {
            $this->setMessage(Craft::t('sprout-module-forms', 'Unable to mark submissions as Spam'));

            return false;
        }

        $this->setMessage(Craft::t('sprout-module-forms', 'Submissions marked as Spam.'));

        return true;
    }
}
