<?php

namespace BarrelStrength\Sprout\core\fieldlayoutelements;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\BaseNativeField;

class SelectField extends BaseNativeField
{
    public array $options = [];

    protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::$app->getView()->renderTemplate('_includes/forms/select.twig', [
            'id' => $this->id(),
            'describedBy' => $this->describedBy($element, $static),
            'name' => $this->name ?? $this->attribute(),
            'options' => $this->options,
            'value' => $this->value($element),
            'required' => !$static && $this->required,
        ]);
    }
}
