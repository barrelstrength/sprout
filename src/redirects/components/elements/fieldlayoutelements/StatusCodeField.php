<?php

namespace BarrelStrength\Sprout\redirects\components\elements\fieldlayoutelements;

use BarrelStrength\Sprout\redirects\redirects\StatusCode;
use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\BaseNativeField;

class StatusCodeField extends BaseNativeField
{
    public string $type = 'select';

    public bool $mandatory = true;

    public string $attribute = 'statusCode';

    public bool $required = true;

    protected function defaultLabel(ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('sprout-module-redirects', 'Status Code');
    }

    protected function value(ElementInterface $element = null): mixed
    {
        return parent::value($element) ?? 302;
    }

    protected function inputHtml(ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::$app->getView()->renderTemplate('_includes/forms/select', [
            'type' => $this->type,
            'describedBy' => $this->describedBy($element, $static),
            'name' => $this->name ?? $this->attribute(),
            'value' => $this->value($element),
            'options' => StatusCode::options(),
        ]);
    }
}
