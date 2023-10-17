<?php

namespace BarrelStrength\Sprout\forms\submissions;

use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use BarrelStrength\Sprout\forms\FormsModule;
use Craft;
use craft\base\Model;
use DateTime;

class SubmissionsSpamLog extends Model
{
    public ?int $id = null;

    public ?int $submissionId = null;

    public string $type;

    public $errors;

    public DateTime|string $dateCreated;

    public DateTime|string $dateUpdated;

    public string $uid;

    /**
     * Use the translated section name as the string representation.
     */
    public function __toString()
    {
        return Craft::t('sprout-module-forms', $this->id);
    }

    public function getSubmission(): ?SubmissionElement
    {
        return FormsModule::getInstance()->submissions->getSubmissionById($this->submissionId);
    }
}
