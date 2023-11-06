<?php

namespace BarrelStrength\Sprout\forms\submissions;

use BarrelStrength\Sprout\forms\captchas\Captcha;
use BarrelStrength\Sprout\forms\components\events\OnBeforeValidateSubmissionEvent;
use BarrelStrength\Sprout\forms\FormsModule;
use Craft;

class SubmissionHelper
{
    public static function validateCaptchas(OnBeforeValidateSubmissionEvent $event): void
    {
        if (!Craft::$app->getRequest()->getIsSiteRequest()) {
            return;
        }

        $enableCaptchas = (int)$event->form->enableCaptchas;

        // Don't process captchas if the form is set to ignore them
        if (!$enableCaptchas) {
            return;
        }

        /** @var Captcha[] $captchas */
        $captchas = FormsModule::getInstance()->formCaptchas->getAllEnabledCaptchas();

        foreach ($captchas as $captcha) {
            $captcha->verifySubmission($event);
            $event->submission->addCaptcha($captcha);
        }
    }
}
