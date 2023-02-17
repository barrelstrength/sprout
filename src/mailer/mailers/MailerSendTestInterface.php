<?php

namespace BarrelStrength\Sprout\mailer\mailers;

use craft\base\Model;

/**
 * Tests can be sent via 'sprout-module-mailer/mailer/send-test'
 */
interface MailerSendTestInterface
{
    /**
     * The settings model that represents any settings necessary for the Mailer sendTest method
     */
    public function createMailerInstructionsTestSettingsModel(): Model;

    /**
     * Test modal html should namespace any fields provided to ensure they
     * get assigned to the `mailerInstructionsSettings` model:
     *
     * {% namespace 'mailerInstructionsSettings' %}
     *
     * @return string
     */
    public function getSendTestModalHtml(): string;
}
