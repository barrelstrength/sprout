<?php

namespace BarrelStrength\Sprout\forms\components\captchas;

use BarrelStrength\Sprout\forms\captchas\Captcha;
use BarrelStrength\Sprout\forms\components\events\OnBeforeValidateSubmissionEvent;
use Craft;
use craft\helpers\Html;

class DuplicateCaptcha extends Captcha
{
    public const DUPLICATE_CAPTCHA_INPUT_KEY = 'sprout-forms-dc';

    public function getName(): string
    {
        return 'Duplicate Submission Captcha';
    }

    public function getDescription(): string
    {
        return Craft::t('sprout-module-forms', 'Prevent duplicate submissions if a user hits submit more than once. This check is handled on the server side in addition to any duplicate submission prevention behavior handled via javascript when the user submits the form.');
    }

    public function getCaptchaHtml(): string
    {
        $inputName = uniqid(self::DUPLICATE_CAPTCHA_INPUT_KEY, false);
        $uniqueKeyId = uniqid('dc', false);

        // Set a session variable with a unique key. It doesn't matter
        // what the value of this is we'll save the unique key in a
        // hidden field and check for and remove the session based
        // on the session key if it exists, so we can only validate
        // a submission the first time it is used.
        Craft::$app->getSession()->set($uniqueKeyId, true);

        return Html::hiddenInput($inputName, $uniqueKeyId);
    }

    public function verifySubmission(OnBeforeValidateSubmissionEvent $event): bool
    {
        $uniqueid = null;

        foreach (array_keys($_POST) as $key) {
            if (str_starts_with($key, self::DUPLICATE_CAPTCHA_INPUT_KEY)) {
                $uniqueid = $_POST[$key];
                break;
            }
        }

        if (!Craft::$app->getSession()->get($uniqueid)) {
            $errorMessage = 'Submission appears to be a duplicate.';
            Craft::error($errorMessage, __METHOD__);

            $this->addError(self::CAPTCHA_ERRORS_KEY, $errorMessage);

            return false;
        }

        // If we have a duplicate key, unset our test variable
        // so we don't have it on the next request
        Craft::$app->getSession()->remove($uniqueid);

        return true;
    }
}
