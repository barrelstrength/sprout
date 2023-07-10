<?php

namespace BarrelStrength\Sprout\transactional\components\mailers;

use BarrelStrength\Sprout\mailer\components\mailers\SystemMailer;
use Craft;

class TransactionalMailer extends SystemMailer
{
    public static function displayName(): string
    {
        return 'Transactional Mailer';
    }

    public function getDescription(): string
    {
        return Craft::t('sprout-module-transactional', 'Smart transactional email, easy recipient management, and advanced third party integrations.');
    }

    public function createMailerInstructionsSettingsModel(): TransactionalMailerInstructionsSettings
    {
        return new TransactionalMailerInstructionsSettings();
    }

    public function createMailerInstructionsTestSettingsModel(): TransactionalMailerInstructionsTestSettings
    {
        return new TransactionalMailerInstructionsTestSettings();
    }
}
