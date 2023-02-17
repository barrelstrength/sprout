<?php

namespace BarrelStrength\Sprout\mailer\components\mailers\fieldlayoutelements;

use BarrelStrength\Sprout\mailer\components\elements\audience\AudienceElement;
use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\BaseNativeField;

class AudienceField extends BaseNativeField
{
    public bool $mandatory = true;

    public string $attribute = 'audienceIds';

    protected function showLabel(): bool
    {
        return false;
    }

    protected function selectorLabel(): ?string
    {
        return Craft::t('sprout-module-mailer', 'Audience');
    }

    protected function inputHtml(ElementInterface $element = null, bool $static = false): ?string
    {
        $mailerInstructionsSettings = $element->getMailerInstructionsSettings();

        return Craft::$app->getView()->renderTemplate('sprout-module-mailer/_components/mailers/SystemMailer/audience-field', [
            'audiences' => $mailerInstructionsSettings->getAudiences(),
            'audienceElementType' => AudienceElement::class,
        ]);
    }
}
