<?php

namespace BarrelStrength\Sprout\mailer\components\elements\email\fieldlayoutelements;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\TextField;

class SubjectLineField extends TextField
{
    public bool $mandatory = true;

    public string $attribute = 'subjectLine';

    public ?int $maxlength = 255;

    public bool $required = true;

    protected function defaultLabel(ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('sprout-module-mailer', 'Subject Line');
    }

    protected function defaultInstructions(ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('sprout-module-mailer', 'The subject line that will be used for the email.');
    }
}
