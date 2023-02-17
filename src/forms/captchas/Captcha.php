<?php

namespace BarrelStrength\Sprout\forms\captchas;

use BarrelStrength\Sprout\forms\components\elements\FormElement;
use BarrelStrength\Sprout\forms\components\events\OnBeforeValidateSubmissionEvent;
use BarrelStrength\Sprout\forms\FormsModule;
use craft\base\Model;

/**
 * Class Captcha
 *
 * @property null $settings
 * @property string $captchaSettingsHtml
 * @property string $name
 * @property string $description
 * @property string $captchaHtml
 */
abstract class Captcha extends Model
{
    /**
     * Add errors to a Captcha using the error key
     * to support spam error logging and reporting
     */
    public const CAPTCHA_ERRORS_KEY = 'captchaErrors';

    /**
     * The form where the captcha is being output
     */
    public FormElement $form;

    /**
     * The name of the captcha
     */
    abstract public function getName(): string;

    /**
     * A description of the captcha behavior
     */
    abstract public function getDescription(): string;

    /**
     * Returns any values saved as settings for this captcha
     */
    public function getSettings(): ?array
    {
        $settings = FormsModule::getInstance()->getSettings();

        return $settings->captchaSettings[$this::class] ?? null;
    }

    /**
     * Returns html to display for your captcha settings.
     *
     * Sprout Forms will display all captcha settings on the Settings->Spam Prevention tab.
     * An option will be displayed to enable/disable each captcha. If your captcha
     * settings are enabled, Sprout Forms will output getCaptchaSettingsHtml for users to
     * customize any additional settings your provide.
     */
    public function getCaptchaSettingsHtml(): string
    {
        return '';
    }

    /**
     * Returns whatever is needed to get your captcha working in the front-end form template
     *
     * Sprout Forms will loop through all enabled Captcha integrations and output
     * getCaptchaHtml when the template hook `sproutForms.modifyForm` in form.html
     * is triggered.
     */
    public function getCaptchaHtml(): string
    {
        return '';
    }

    /**
     * Returns if a form submission passes or fails your captcha validation.
     */
    public function verifySubmission(OnBeforeValidateSubmissionEvent $event): bool
    {
        return true;
    }
}
