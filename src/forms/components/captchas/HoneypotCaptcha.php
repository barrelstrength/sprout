<?php

namespace BarrelStrength\Sprout\forms\components\captchas;

use BarrelStrength\Sprout\forms\captchas\Captcha;
use BarrelStrength\Sprout\forms\components\events\OnBeforeValidateSubmissionEvent;
use Craft;

class HoneypotCaptcha extends Captcha
{
    public const HONEYPOT_CAPTCHA_INPUT_KEY = 'sprout-forms-hc';

    public string $honeypotFieldName;

    public string $honeypotScreenReaderMessage;

    public function getName(): string
    {
        return 'Honeypot Captcha';
    }

    public function getDescription(): string
    {
        return Craft::t('sprout-module-forms', 'Block form submissions by robots who auto-fill all of your form fields ');
    }

    public function getCaptchaSettingsHtml(): string
    {
        $settings = $this->getSettings();

        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/captchas/Honeypot/settings', [
            'captcha' => $this,
            'settings' => $settings,
            'defaultFieldName' => self::HONEYPOT_CAPTCHA_INPUT_KEY,
        ]);
    }

    public function getCaptchaHtml(): string
    {
        $this->honeypotFieldName = $this->getHoneypotFieldName();
        $this->honeypotScreenReaderMessage = $this->getHoneypotScreenReaderMessage();

        $uniqueId = uniqid($this->honeypotFieldName, false);

        return '
    <div id="' . $uniqueId . '_wrapper" style="display:none;">
        <label for="' . $uniqueId . '">' . $this->honeypotScreenReaderMessage . '</label>
        <input type="text" id="' . $uniqueId . '" name="' . $uniqueId . '" value="" />
    </div>';
    }

    public function verifySubmission(OnBeforeValidateSubmissionEvent $event): bool
    {
        $honeypotFieldName = $this->getHoneypotFieldName();

        $honeypotValue = null;

        foreach (array_keys($_POST) as $key) {
            if (str_starts_with($key, $honeypotFieldName)) {
                $honeypotValue = $_POST[$key];
                break;
            }
        }

        // The honeypot field must be left blank
        if ($honeypotValue) {
            $errorMessage = 'Honeypot must be blank. Value submitted: ' . $honeypotValue;
            Craft::error($errorMessage, __METHOD__);

            $this->addError(self::CAPTCHA_ERRORS_KEY, $errorMessage);

            return false;
        }

        return true;
    }

    public function getHoneypotFieldName(): string
    {
        $settings = $this->getSettings();

        return $settings['honeypotFieldName'];
    }

    public function getHoneypotScreenReaderMessage(): string
    {
        $settings = $this->getSettings();

        return $settings['honeypotScreenReaderMessage'] ?? Craft::t('sprout-module-forms', 'Leave this field blank');
    }
}
