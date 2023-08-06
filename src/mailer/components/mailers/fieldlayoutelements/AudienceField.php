<?php

namespace BarrelStrength\Sprout\mailer\components\mailers\fieldlayoutelements;

use BarrelStrength\Sprout\mailer\components\elements\audience\AudienceElement;
use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use Craft;
use craft\base\ElementInterface;
use craft\errors\MissingComponentException;
use craft\fieldlayoutelements\BaseNativeField;
use craft\helpers\Html;
use craft\helpers\UrlHelper;

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
        if (!$element instanceof EmailElement) {
            throw new MissingComponentException('Email Element must exist before rendering edit page.');
        }

        $mailerInstructionsSettings = $element->getMailerInstructions();

        $audiences = $mailerInstructionsSettings->getAudiences();
        $audiencesExist = AudienceElement::find()->exists();

        $addAudienceLink = Html::a(Craft::t('sprout-module-mailer', 'Add an audience.'), UrlHelper::cpUrl('sprout/email/audiences'));

        return Craft::$app->getView()->renderTemplate('sprout-module-mailer/_components/mailers/SystemMailer/audience-field.twig', [
            'audienceElementType' => AudienceElement::class,
            'audiences' => $audiences,
            'audiencesExist' => $audiencesExist,
            'addAudienceLink' => $addAudienceLink,
        ]);
    }
}
