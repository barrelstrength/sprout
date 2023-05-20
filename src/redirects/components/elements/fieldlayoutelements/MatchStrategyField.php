<?php

namespace BarrelStrength\Sprout\redirects\components\elements\fieldlayoutelements;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\BaseNativeField;

class MatchStrategyField extends BaseNativeField
{
    public string $type = 'select';

    public bool $mandatory = true;

    public string $attribute = 'matchStrategy';

    public bool $required = true;

    protected function defaultLabel(ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('sprout-module-redirects', 'Match Strategy');
    }

    protected function defaultInstructions(ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::t('sprout-module-redirects', 'The method used when checking if a 404 matches the Old URL path of a Redirect.');
    }

    protected function inputHtml(ElementInterface $element = null, bool $static = false): ?string
    {
        $options = [
            [
                'value' => 'exactMatch',
                'label' => 'Exact Match',
            ],
            [
                'value' => 'regExMatch',
                'label' => 'Regular Expression',
            ],
        ];

        return Craft::$app->getView()->renderTemplate('_includes/forms/select.twig', [
            'type' => $this->type,
            'describedBy' => $this->describedBy($element, $static),
            'name' => $this->name ?? $this->attribute(),
            'value' => $this->value($element),
            'options' => $options,
        ]);
    }
}
