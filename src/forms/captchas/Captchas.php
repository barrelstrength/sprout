<?php

namespace BarrelStrength\Sprout\forms\captchas;

use BarrelStrength\Sprout\forms\components\captchas\DuplicateCaptcha;
use BarrelStrength\Sprout\forms\components\captchas\GoogleCaptcha;
use BarrelStrength\Sprout\forms\components\captchas\HoneypotCaptcha;
use BarrelStrength\Sprout\forms\components\captchas\JavascriptCaptcha;
use BarrelStrength\Sprout\forms\components\events\OnBeforeValidateSubmissionEvent;
use BarrelStrength\Sprout\forms\FormsModule;
use Craft;
use craft\events\RegisterComponentTypesEvent;
use yii\base\Component;

class Captchas extends Component
{
    public const EVENT_REGISTER_CAPTCHAS = 'registerSproutCaptchas';

    /**
     * Returns all available Captcha classes
     */
    public function getAllCaptchaTypes(): array
    {
        $captchas = [
            DuplicateCaptcha::class,
            GoogleCaptcha::class,
            HoneypotCaptcha::class,
            JavascriptCaptcha::class,
        ];

        $event = new RegisterComponentTypesEvent([
            'types' => $captchas,
        ]);

        $this->trigger(self::EVENT_REGISTER_CAPTCHAS, $event);

        return $event->types;
    }

    public function getAllCaptchas(): array
    {
        $captchaTypes = $this->getAllCaptchaTypes();
        $captchas = [];

        foreach ($captchaTypes as $captchaType) {
            $captchas[$captchaType] = new $captchaType();
        }

        return $captchas;
    }

    public static function handleValidateCaptchas(OnBeforeValidateSubmissionEvent $event): void
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
        $captchas = FormsModule::getInstance()->captchas->getAllEnabledCaptchas();

        foreach ($captchas as $captcha) {
            $captcha->verifySubmission($event);
            $event->submission->addCaptcha($captcha);
        }
    }

    public function getAllEnabledCaptchas(): array
    {
        $sproutFormsSettings = FormsModule::getInstance()->getSettings();
        $captchaTypes = $this->getAllCaptchas();
        $captchas = [];

        foreach ($captchaTypes as $captchaType) {
            $isEnabled = $sproutFormsSettings->captchaSettings[$captchaType::class]['enabled'] ?? false;
            if ($isEnabled) {
                $captchas[$captchaType::class] = $captchaType;
            }
        }

        return $captchas;
    }
}
