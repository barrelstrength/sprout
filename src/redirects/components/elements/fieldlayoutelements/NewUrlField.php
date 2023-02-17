<?php

namespace BarrelStrength\Sprout\redirects\components\elements\fieldlayoutelements;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\TextField;

class NewUrlField extends TextField
{
    public bool $mandatory = true;

    public string $attribute = 'newUrl';

    public ?int $maxlength = 255;

    public bool $autofocus = true;

    public ?string $placeholder = '';

    protected function defaultLabel(ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('sprout-module-redirects', 'New URL');
    }

    protected function defaultInstructions(ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('sprout-module-redirects', 'The relative path of the new location. Leave blank to redirect to the site home page.');
    }
}
