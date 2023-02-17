<?php

namespace BarrelStrength\Sprout\redirects\components\elements\fieldlayoutelements;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\TextField;

class OldUrlField extends TextField
{
    public bool $mandatory = true;

    public string $attribute = 'oldUrl';

    public ?int $maxlength = 255;

    public bool $required = true;

    public ?string $placeholder = 'old/page/location';

    protected function defaultLabel(ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('sprout-module-redirects', 'Old URL');
    }

    protected function defaultInstructions(ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('sprout-module-redirects', 'The relative path of the old location.');
    }
}
