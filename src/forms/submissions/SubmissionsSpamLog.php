<?php

namespace BarrelStrength\Sprout\forms\submissions;

use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use BarrelStrength\Sprout\forms\FormsModule;
use Craft;
use craft\base\Model;

class SubmissionsSpamLog extends Model
{
    public ?int $id = null;

    public ?int $submissionId = null;

    public string $type;

    public $errors;

    public string $dateCreated;

    public string $dateUpdated;

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
