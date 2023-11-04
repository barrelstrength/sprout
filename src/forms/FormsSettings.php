<?php

namespace BarrelStrength\Sprout\forms;

use BarrelStrength\Sprout\forms\components\captchas\DuplicateCaptcha;
use BarrelStrength\Sprout\forms\components\captchas\HoneypotCaptcha;
use BarrelStrength\Sprout\forms\components\captchas\JavascriptCaptcha;
use BarrelStrength\Sprout\forms\forms\Submissions;
use craft\config\BaseConfig;

class FormsSettings extends BaseConfig
{
    public const SPAM_REDIRECT_BEHAVIOR_NORMAL = 'redirectAsNormal';

    public const SPAM_REDIRECT_BEHAVIOR_BACK_TO_FORM = 'redirectBackToForm';

    private const DEFAULT_CAPTCHA_SETTINGS = [
        DuplicateCaptcha::class => [
            'enabled' => false,
        ],
        JavascriptCaptcha::class => [
            'enabled' => true,
        ],
        HoneypotCaptcha::class => [
            'enabled' => false,
            'honeypotFieldName' => 'sprout-forms-hc',
            'honeypotScreenReaderMessage' => 'Leave this field blank',
        ],
    ];

    public string $defaultSidebarTab = 'submissions';

    public string $defaultSubmissionMethod = 'sync';

    public array $integrationTypes = [];

    public array $formTypes = [];

    public ?string $formTypeUid = null;

    public bool $enableSaveData = true;

    public bool $saveSpamToDatabase = false;

    public bool $enableSaveDataDefaultValue = true;

    public string $spamRedirectBehavior = self::SPAM_REDIRECT_BEHAVIOR_NORMAL;

    public int $spamLimit = Submissions::SPAM_DEFAULT_LIMIT;

    public int $cleanupProbability = 1000;

    public bool $trackRemoteIp = false;

    public array $captchaSettings = self::DEFAULT_CAPTCHA_SETTINGS;

    public bool $enableEditSubmissionViaFrontEnd = false;

    public string|array $allowedAssetVolumes = '*';

    public string $defaultUploadLocationSubpath = '';

    /**
     * The Field Layout Config that will be saved to Project Config
     */
    public array $fieldLayouts = [];

    public function defaultSidebarTab(string $value): self
    {
        $this->defaultSidebarTab = $value;

        return $this;
    }

    public function defaultSubmissionMethod(string $value): self
    {
        $this->defaultSubmissionMethod = $value;

        return $this;
    }

    public function formTypeUid(string $value): self
    {
        $this->formTypeUid = $value;

        return $this;
    }

    public function enableSaveData(bool $value): self
    {
        $this->enableSaveData = $value;

        return $this;
    }

    public function saveSpamToDatabase(bool $value): self
    {
        $this->saveSpamToDatabase = $value;

        return $this;
    }

    public function enableSaveDataDefaultValue(bool $value): self
    {
        $this->enableSaveDataDefaultValue = $value;

        return $this;
    }

    public function spamRedirectBehavior(string $value): self
    {
        $this->spamRedirectBehavior = $value;

        return $this;
    }

    public function spamLimit(int $value): self
    {
        $this->spamLimit = $value;

        return $this;
    }

    public function cleanupProbability(int $value): self
    {
        $this->cleanupProbability = $value;

        return $this;
    }

    public function trackRemoteIp(bool $value): self
    {
        $this->trackRemoteIp = $value;

        return $this;
    }

    public function captchaSettings(array $value): self
    {
        $this->captchaSettings = $value;

        return $this;
    }

    public function enableEditSubmissionViaFrontEnd(bool $value): self
    {
        $this->enableEditSubmissionViaFrontEnd = $value;

        return $this;
    }

    public function allowedAssetVolumes(string|array $value): self
    {
        $this->allowedAssetVolumes = $value;

        return $this;
    }

    public function defaultUploadLocationSubpath(string|null $value): self
    {
        $this->defaultUploadLocationSubpath = $value;

        return $this;
    }

    public function setAttributes($values, $safeOnly = true): void
    {
        parent::setAttributes($values, $safeOnly);
        foreach (array_keys($this->captchaSettings) as $captchaType) {
            $this->captchaSettings[$captchaType]['enabled'] =
                (bool)$this->captchaSettings[$captchaType]['enabled'];
        }
    }

    public function getSpamRedirectBehaviorsAsOptions(): array
    {
        return [
            [
                'label' => 'Redirect as normal (recommended)',
                'value' => self::SPAM_REDIRECT_BEHAVIOR_NORMAL,
            ],
            [
                'label' => 'Redirect back to form',
                'value' => self::SPAM_REDIRECT_BEHAVIOR_BACK_TO_FORM,
            ],
        ];
    }
}

