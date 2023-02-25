<?php

namespace BarrelStrength\Sprout\mailer;

use craft\config\BaseConfig;

class MailerSettings extends BaseConfig
{
    public bool $enableSubscriberLists = false;

    public ?array $approvedSenders = null;

    public ?array $approvedReplyToEmails = null;

    public array $emailThemes = [];


    public array $mailers = [];

    public function enableSubscriberLists(bool $value): self
    {
        $this->enableSubscriberLists = $value;

        return $this;
    }

    public function approvedSenders(array $value): self
    {
        $this->approvedSenders = $value;

        return $this;
    }

    public function approvedReplyToEmails(array $value): self
    {
        $this->approvedReplyToEmails = $value;

        return $this;
    }

    public function emailThemes(array $value): self
    {
        $this->emailThemes = $value;

        return $this;
    }
}

