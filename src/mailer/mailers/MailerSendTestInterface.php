<?php

namespace BarrelStrength\Sprout\mailer\mailers;

use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;

/**
 * Tests can be sent via 'sprout-module-mailer/mailer/send-test'
 */
interface MailerSendTestInterface
{
    /**
     * The settings model that represents any settings necessary for the Mailer sendTest method
     */
    public function createMailerInstructionsTestSettingsModel(): MailerInstructionsInterface;

    /**
     * Test modal html should namespace any fields provided to ensure they
     * get assigned to the `mailerInstructionsSettings` model:
     *
     * {% namespace 'mailerInstructionsSettings' %}
     */
    public function getSendTestModalHtml(EmailElement $email): string;
}
