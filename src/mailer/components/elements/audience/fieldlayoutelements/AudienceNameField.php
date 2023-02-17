<?php

namespace BarrelStrength\Sprout\mailer\components\elements\audience\fieldlayoutelements;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\TextField;

class AudienceNameField extends TextField
{
    public bool $mandatory = true;

    public string $attribute = 'name';

    public ?int $maxlength = 255;

    public bool $required = true;

    public bool $autofocus = true;

    public function defaultLabel(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('sprout-module-mailer', 'Audience Name');
    }
}
