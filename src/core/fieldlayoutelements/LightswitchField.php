<?php

namespace BarrelStrength\Sprout\core\fieldlayoutelements;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\BaseNativeField;

class LightswitchField extends BaseNativeField
{
    public ?string $onLabel = null;

    public ?string $offLabel = null;

    protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::$app->getView()->renderTemplate('_includes/forms/lightswitch.twig', [
            'id' => $this->id(),
            'describedBy' => $this->describedBy($element, $static),
            'name' => $this->name ?? $this->attribute(),
            'required' => !$static && $this->required,
            'on' => $this->value($element),
            'onLabel' => $this->onLabel,
            'offLabel' => $this->offLabel,
        ]);
    }
}
