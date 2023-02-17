<?php

namespace BarrelStrength\Sprout\mailer\components\elements\audience\fieldlayoutelements;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\TextField;

class AudienceHandleField extends TextField
{
    public bool $mandatory = true;

    public string $attribute = 'handle';

    public ?int $maxlength = 255;

    public bool $required = true;

    protected function defaultLabel(ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('sprout-module-mailer', 'Handle');
    }

    protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        Craft::$app->getView()->registerJs("new Craft.HandleGenerator('#name', '#handle');");

        return parent::inputHtml($element, $static);
    }
}
