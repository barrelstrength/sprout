<?php

namespace BarrelStrength\Sprout\mailer\components\emailtypes\fieldlayoutfields;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\TextareaField;

class DefaultMessageField extends TextareaField
{
    public const FIELD_LAYOUT_ATTRIBUTE = 'defaultMessage';

    public string $attribute = self::FIELD_LAYOUT_ATTRIBUTE;

    public string|array|null $class = 'nicetext fullwidth';

    public ?int $rows = 11;

    public ?int $maxlength = 255;

    protected function defaultLabel(ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('sprout-module-mailer', 'Message');
    }

    protected function defaultInstructions(ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('sprout-module-mailer', 'A message that will appear in the body of your email content.');
    }
}
