<?php

namespace BarrelStrength\Sprout\mailer\mailers;

use BarrelStrength\Sprout\transactional\components\mailers\TransactionalMailer;
use craft\events\DefineFieldLayoutElementsEvent;
use craft\events\DefineFieldLayoutFieldsEvent;
use craft\models\FieldLayout;

class MailerHelper
{
    public static function defineNativeFields(DefineFieldLayoutFieldsEvent $event): void
    {
        /** @var FieldLayout $fieldLayout */
        $fieldLayout = $event->sender;

        if (!$fieldLayout->type) {
            return;
        }

        /** @var MailerInterface $type */
        $type = new $fieldLayout->type();

        if ($type instanceof MailerInterface) {
            $event->fields = array_merge($event->fields, $type::defineNativeFields($event));
        }
    }

    public static function defineNativeElements(DefineFieldLayoutElementsEvent $event): void
    {
        /** @var FieldLayout $fieldLayout */
        $fieldLayout = $event->sender;

        if (!$fieldLayout->type) {
            return;
        }

        /** @var MailerInterface $type */
        $type = new $fieldLayout->type();

        if ($type instanceof MailerInterface) {
            // For some reason this runs twice, so this ridiculous code is
            // preventing duplicates until we figure out why
            $classNames = array_map(static function($element) {
                if (is_object($element)) {
                    return $element::class;
                } else {
                    return $element;
                }
            }, $event->elements);

            $newElements = $type::defineNativeElements($event);
            foreach ($newElements as $newElement) {
                if (!in_array($newElement::class, $classNames, true)) {
                    $event->elements[] = $newElement;
                }
            }
        }
    }
}
