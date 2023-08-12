<?php

namespace BarrelStrength\Sprout\mailer\components\elements\subscriber;

use BarrelStrength\Sprout\mailer\components\audiences\SubscriberListAudienceType;
use BarrelStrength\Sprout\mailer\components\elements\audience\AudienceElement;
use BarrelStrength\Sprout\mailer\components\elements\subscriber\fieldlayoutelements\SubscriberListsField;
use BarrelStrength\Sprout\mailer\MailerModule;
use Craft;
use craft\elements\User;
use craft\events\DefineBehaviorsEvent;
use craft\events\DefineFieldLayoutFieldsEvent;
use craft\events\RegisterElementSourcesEvent;

class SubscriberHelper
{
    public static function attachSubscriberElementBehavior(DefineBehaviorsEvent $event): void
    {
        $settings = MailerModule::getInstance()->getSettings();

        if ($settings->enableSubscriberLists) {
            $event->behaviors[SproutSubscriberElementBehavior::class] = SproutSubscriberElementBehavior::class;
        }
    }

    public static function attachSubscriberQueryBehavior(DefineBehaviorsEvent $event): void
    {
        $settings = MailerModule::getInstance()->getSettings();

        if ($settings->enableSubscriberLists) {
            $event->behaviors[SproutSubscriberQueryBehavior::class] = SproutSubscriberQueryBehavior::class;
        }
    }

    public static function defineNativeSubscriberField(DefineFieldLayoutFieldsEvent $event): void
    {
        if ($event->sender->type !== User::class) {
            return;
        }

        $settings = MailerModule::getInstance()->getSettings();

        if ($settings->enableSubscriberLists) {
            $event->fields[] = SubscriberListsField::class;
        }
    }

    public static function defineAdditionalSources(RegisterElementSourcesEvent $event): void
    {
        if ($event->context !== 'index') {
            return;
        }

        $settings = MailerModule::getInstance()->getSettings();

        if (!$settings->enableSubscriberLists) {
            return;
        }

        /** @var AudienceElement[] $subscriberLists */
        $subscriberLists = AudienceElement::find()
            ->type(SubscriberListAudienceType::class)
            ->all();

        $sources = [];

        if (!empty($subscriberLists)) {
            $sources[] = [
                'heading' => Craft::t('sprout-module-mailer', 'Subscriber Lists'),
            ];

            foreach ($subscriberLists as $list) {
                $source = [
                    'key' => 'subscriber-lists:' . $list->getId(),
                    'label' => $list->name,
                    'data' => [
                        'handle' => $list->handle,
                    ],
                    'criteria' => [
                        'sproutSubscriberListId' => $list->getId(),
                    ],
                ];

                $sources[] = $source;
            }
        }

        $event->sources = array_merge($event->sources, $sources);
    }
}
