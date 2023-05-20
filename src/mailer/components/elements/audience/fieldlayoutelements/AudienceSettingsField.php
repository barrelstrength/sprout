<?php

namespace BarrelStrength\Sprout\mailer\components\elements\audience\fieldlayoutelements;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\TextField;

class AudienceSettingsField extends TextField
{
    public string $attribute = 'settings';

    public bool $mandatory = true;

    public bool $required = true;

    protected function showLabel(): bool
    {
        return false;
    }

    protected function selectorLabel(): ?string
    {
        return Craft::t('sprout-module-mailer', 'Audience Settings');
    }

    protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-mailer/audience/settings', [
            'audience' => $element->getAudience(),
            'static' => $static,
        ]);
    }
}
