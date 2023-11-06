<?php

namespace BarrelStrength\Sprout\forms\components\emailtypes;

use BarrelStrength\Sprout\mailer\components\emailtypes\fieldlayoutfields\DefaultMessageField;
use BarrelStrength\Sprout\mailer\emailtypes\EmailType;
use Craft;
use craft\events\DefineFieldLayoutFieldsEvent;
use craft\fieldlayoutelements\Tip;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;

class FormSummaryEmailType extends EmailType
{
    public bool $displayPreheaderText = false;

    public ?string $htmlEmailTemplate = '@Sprout/TemplateRoot/emails/submission/email.twig';

    public static function displayName(): string
    {
        return Craft::t('sprout-module-forms', 'Form Summary (Sprout)');
    }

    public function getTemplateRoot(): string
    {
        return Craft::getAlias('@Sprout/TemplateRoot');
    }

    public function getPath(): string
    {
        return 'emails/submission';
    }

    public static function defineNativeFields(DefineFieldLayoutFieldsEvent $event): void
    {
        $event->fields[DefaultMessageField::class] = DefaultMessageField::class;
    }

    public function createFieldLayout(): ?FieldLayout
    {
        $fieldLayout = new FieldLayout([
            'type' => self::class,
        ]);

        $fieldLayoutTab = new FieldLayoutTab([
            'layout' => $fieldLayout,
            'name' => Craft::t('sprout-module-forms', 'Content'),
            'sortOrder' => 1,
            'uid' => StringHelper::UUID(),
        ]);

        $fieldLayoutTab->setElements([
            new DefaultMessageField(),
            new Tip([
                'style' => Tip::STYLE_TIP,
                'tip' => Craft::t('sprout-module-forms', 'The body of this email will include a summary of the form submission.'),
                'uid' => StringHelper::UUID(),
            ]),
        ]);

        $fieldLayout->setTabs([
            $fieldLayoutTab,
        ]);

        return $fieldLayout;
    }
}
