<?php

namespace BarrelStrength\Sprout\mailer\components\elements\audience\fieldlayoutelements;

use BarrelStrength\Sprout\mailer\components\elements\audience\AudienceElement;
use Craft;
use craft\base\ElementInterface;
use craft\errors\MissingComponentException;
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
        if (!$element instanceof AudienceElement) {
            throw new MissingComponentException('Audience Element must exist before rendering edit page.');
        }

        return Craft::$app->getView()->renderTemplate('sprout-module-mailer/audience/settings.twig', [
            'audience' => $element->getAudienceType(),
            'static' => $static,
        ]);
    }
}
