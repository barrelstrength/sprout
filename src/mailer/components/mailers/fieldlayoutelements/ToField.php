<?php

namespace BarrelStrength\Sprout\mailer\components\mailers\fieldlayoutelements;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\TextField;

class ToField extends TextField
{
    public bool $mandatory = true;

    public string $attribute = 'recipients';

    public ?string $name = 'mailerInstructionsSettings[recipients]';

    public string|array|null $class = 'code';

    public ?string $placeholder = 'user@domain.com, First Last <user@domain.com>';

    protected function defaultLabel(ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('sprout-module-mailer', 'To');
    }

    protected function defaultInstructions(ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('sprout-module-mailer', 'Comma-separated list of recipients');
    }

    protected function value(?ElementInterface $element = null): mixed
    {
        $mailerInstructionsSettings = $element->getMailerInstructionsSettings();

        return $mailerInstructionsSettings->{$this->attribute()} ?? null;
    }
}
