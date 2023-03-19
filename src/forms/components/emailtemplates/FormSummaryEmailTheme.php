<?php

namespace BarrelStrength\Sprout\forms\components\emailtemplates;

use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\mailer\emailthemes\EmailTheme;
use Craft;
use craft\fieldlayoutelements\TextareaField;
use craft\helpers\StringHelper;
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
        return 'emailtemplates/submission';
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
                'label' => Craft::t('sprout-module-mailer', 'Body'),
                'instructions' => Craft::t('sprout-module-mailer', 'Instructions'),
                'attribute' => 'defaultBody',
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



