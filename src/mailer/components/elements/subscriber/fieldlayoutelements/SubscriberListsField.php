<?php

namespace BarrelStrength\Sprout\mailer\components\elements\subscriber\fieldlayoutelements;

use BarrelStrength\Sprout\mailer\components\audiences\SubscriberListAudienceType;
use BarrelStrength\Sprout\mailer\components\elements\audience\AudienceElement;
use BarrelStrength\Sprout\mailer\components\elements\subscriber\SproutSubscriberElementBehavior;
use BarrelStrength\Sprout\mailer\MailerModule;
use Craft;
use craft\base\ElementInterface;
use craft\elements\User;
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
        if (!$element instanceof User) {
            return '';
        }

        /** @var ElementInterface|SproutSubscriberElementBehavior $element */
        if (!$element->getBehavior(SproutSubscriberElementBehavior::class)) {
            return '';
        }

        return Craft::$app->getView()->renderTemplate('sprout-module-mailer/subscribers/_fields.twig', [
            'options' => self::getSubscriberListOptions(),
            'values' => array_keys($element->getSproutSubscriptions()),
        ]);
    }

    protected static function getSubscriberListOptions(): array
    {
        /** @var AudienceElement[] $lists */
        $lists = AudienceElement::find()
            ->type(SubscriberListAudienceType::class)
            ->all();

        $options = [];

        array_map(static function($list) use (&$options) {
            $options[] = [
                'label' => $list->name,
                'value' => $list->getId(),
            ];
        }, $lists);

        return $options;
    }
}
