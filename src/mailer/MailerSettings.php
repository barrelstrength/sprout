<?php

namespace BarrelStrength\Sprout\mailer;

use craft\config\BaseConfig;

class MailerSettings extends BaseConfig
{
    public array $emailTypes = [];

    public array $mailers = [];

    public ?bool $enableSubscriberLists = false;

    public function mailers(array $value): self
    {
        $this->mailers = $value;

        return $this;
    }

    public function emailTypes(array $value): self
    {
        $this->emailTypes = $value;

        return $this;
    }

    public function enableSubscriberLists(bool $value): self
    {
        $this->enableSubscriberLists = $value;

        return $this;
    }
}

