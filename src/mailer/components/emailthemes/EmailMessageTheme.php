<?php

namespace BarrelStrength\Sprout\mailer\components\emailthemes;

use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\mailer\components\emailthemes\fieldlayoutfields\DefaultMessageField;
use BarrelStrength\Sprout\mailer\emailthemes\EmailTheme;
use Craft;
use craft\events\DefineFieldLayoutFieldsEvent;
use craft\fieldlayoutelements\TextareaField;
use craft\fieldlayoutelements\TitleField;
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

    public static function defineNativeFields(DefineFieldLayoutFieldsEvent $event): void
    {
        $event->fields[] = DefaultMessageField::class;
    }

    public function getFieldLayout(): FieldLayout
    {
        if ($this->_fieldLayout) {
            return $this->_fieldLayout;
        }

        $fieldLayout = new FieldLayout([
            'type' => self::class,
        ]);

        $fieldLayoutTab = new FieldLayoutTab([
            'layout' => $fieldLayout,
            'name' => Craft::t('sprout-module-mailer', 'Content'),
            'sortOrder' => 1,
            'uid' => StringHelper::UUID(),
        ]);

        $fieldLayout->setTabs([
            $fieldLayoutTab,
        ]);

        return $this->_fieldLayout = $fieldLayout;
    }
}



