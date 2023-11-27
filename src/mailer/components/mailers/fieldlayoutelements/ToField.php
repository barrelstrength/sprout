<?php

namespace BarrelStrength\Sprout\mailer\components\mailers\fieldlayoutelements;

use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use Craft;
use craft\base\ElementInterface;
use craft\errors\MissingComponentException;
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

    protected function tip(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('sprout-module-mailer', 'This can use Twig Shortcut Syntax and reference Notification Event variables.');
    }

    protected function value(?ElementInterface $element = null): mixed
    {
        if (!$element instanceof EmailElement) {
            throw new MissingComponentException('Email Element must exist before rendering edit page.');
        }

        $mailerInstructionsSettings = $element->getMailerInstructions();

        return $mailerInstructionsSettings->{$this->attribute()} ?? null;
    }
}
