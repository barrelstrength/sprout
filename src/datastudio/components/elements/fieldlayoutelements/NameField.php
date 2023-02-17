<?php

namespace BarrelStrength\Sprout\datastudio\components\elements\fieldlayoutelements;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\TextField;

class NameField extends TextField
{
    public bool $mandatory = true;

    public bool $required = true;

    public string $attribute = 'name';

    public ?int $maxlength = 255;

    public bool $autofocus = true;

    protected function defaultLabel(ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('sprout-module-data-studio', 'Name');
    }
}
