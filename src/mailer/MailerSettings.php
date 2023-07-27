<?php

namespace BarrelStrength\Sprout\mailer;

use craft\config\BaseConfig;

class MailerSettings extends BaseConfig
{
    public array $emailThemes = [];

    public array $mailers = [];

    public array $systemMailer = [];


    public bool $enableSubscriberLists = false;

    public function systemMailer(array $value): self
    {
        $this->systemMailer = $value;

        return $this;
    }

    public function mailers(array $value): self
    {
        $this->mailers = $value;

        return $this;
    }

    public function emailThemes(array $value): self
    {
        $this->emailThemes = $value;

        return $this;
    }

    public function enableSubscriberLists(bool $value): self
    {
        $this->enableSubscriberLists = $value;

        return $this;
    }
}

