<?php

namespace BarrelStrength\Sprout\forms\integrations;

use BarrelStrength\Sprout\forms\FormsModule;
use Craft;
use craft\base\Model;

class IntegrationLog extends Model
{
    public ?int $id = null;

    public ?int $submissionId = null;

    public int $integrationId;

    public bool $success = false;

    public string $status;

    public string $message;

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

    public function getIntegration(): ?Integration
    {
        return FormsModule::getInstance()->formIntegrations->getIntegrationById($this->integrationId);
    }
}
