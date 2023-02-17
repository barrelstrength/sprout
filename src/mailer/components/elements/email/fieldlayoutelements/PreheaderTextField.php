<?php

namespace BarrelStrength\Sprout\mailer\components\elements\email\fieldlayoutelements;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\TextField;

class PreheaderTextField extends TextField
{
    public string $attribute = 'preheaderText';

    public ?int $maxlength = 255;

    protected function defaultLabel(ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('sprout-module-mailer', 'Preheader Text');
    }

    protected function defaultInstructions(ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('sprout-module-mailer', 'A preview of the email content in addition to the subject line.');
    }
}
