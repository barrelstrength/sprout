<?php

namespace BarrelStrength\Sprout\forms\components\emailthemes;

use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\mailer\emailthemes\EmailTheme;
use Craft;
use craft\fieldlayoutelements\TextareaField;
use craft\fieldlayoutelements\Tip;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;

class FormSummaryEmailTheme extends EmailTheme
{
    public ?string $htmlEmailTemplate = '@Sprout/TemplateRoot/emails/submission/email.twig';

    public static function displayName(): string
    {
        return Craft::t('sprout-module-forms', 'Form Summary (Sprout)');
    }

    public static function getHandle(): string
    {
        return 'formSummary';
    }

    public function getTemplateRoot(): string
    {
        return Craft::getAlias('@Sprout/TemplateRoot');
    }

    public function getPath(): string
    {
        return 'emails/submission';
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
                'uid' => 'SPROUT-UID-DEFAULT-MESSAGE-FIELD',
            ]),
            new Tip([
                'style' => Tip::STYLE_TIP,
                'tip' => Craft::t('sprout-module-mailer', 'The body of this email theme will include a summary of the form submission.'),
                'uid' => 'SPROUT-UID-FORM-SUMMARY-TIP-FIELD',
            ]),
        ]);

        $fieldLayout->setTabs([
            $fieldLayoutTab,
        ]);

        return $fieldLayout;
    }
}



