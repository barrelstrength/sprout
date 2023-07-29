<?php

namespace BarrelStrength\Sprout\mailer\components\elements\subscriber\fieldlayoutelements;

use BarrelStrength\Sprout\mailer\components\elements\subscriber\SubscriberElementBehavior;
use BarrelStrength\Sprout\mailer\MailerModule;
use BarrelStrength\Sprout\mailer\subscribers\SubscriberHelper;
use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\TextField;

class SubscriberListsField extends TextField
{
    public string $attribute = 'subscriberLists';

    public function mandatory(): bool
    {
        $settings = MailerModule::getInstance()->getSettings();

        return $settings->enableSubscriberLists;
    }

    protected function selectorLabel(): ?string
    {
        return Craft::t('sprout-module-mailer', 'Subscriber Lists');
    }

    protected function inputHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        /** @var SubscriberElementBehavior $element */
        return Craft::$app->getView()->renderTemplate('sprout-module-mailer/subscribers/_fields.twig', [
            'options' => SubscriberHelper::getSubscriberListOptions(),
            'values' => $element->getSubscriberListsIds(),
        ]);
    }
}
