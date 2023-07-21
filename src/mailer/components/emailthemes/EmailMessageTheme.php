<?php

namespace BarrelStrength\Sprout\mailer\components\emailthemes;

use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\mailer\emailthemes\EmailTheme;
use Craft;
use craft\fieldlayoutelements\TextareaField;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;

class EmailMessageTheme extends EmailTheme
{
    public ?string $htmlEmailTemplate = '@Sprout/TemplateRoot/emails/default/email.twig';

    public static function displayName(): string
    {
        return Craft::t('sprout-module-mailer', 'Simple Message');
    }

    public static function getHandle(): string
    {
        return 'simpleMessage';
    }

    public function getFieldLayout(): FieldLayout
    {
        $fieldLayout = new FieldLayout([
            'type' => EmailElement::class,
        ]);

        $fieldLayoutTab = new FieldLayoutTab([
            'layout' => $fieldLayout,
            'name' => Craft::t('sprout-module-mailer', 'Content'),
            'sortOrder' => 1,
            'uid' => 'SPROUT-UID-CONTENT-TAB',
        ]);

        $fieldLayoutTab->setElements([
            new TextareaField([
                'label' => Craft::t('sprout-module-mailer', 'Message'),
                'instructions' => Craft::t('sprout-module-mailer', 'A message that will appear in the body of your email content.'),
                'attribute' => 'defaultMessage',
                'class' => 'nicetext fullwidth',
                'rows' => 11,
                'mandatory' => true,
                'uid' => StringHelper::UUID(),
            ]),
        ]);

        $fieldLayout->setTabs([
            $fieldLayoutTab,
        ]);

        return $fieldLayout;
    }
}



