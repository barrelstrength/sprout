<?php

namespace BarrelStrength\Sprout\datastudio\components\elements\fieldlayoutelements;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\TextField;

class DescriptionField extends TextField
{
    public bool $mandatory = true;

    public string $attribute = 'description';

    public ?int $maxlength = 255;

    protected function defaultLabel(ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('sprout-module-data-studio', 'Description');
    }
}
