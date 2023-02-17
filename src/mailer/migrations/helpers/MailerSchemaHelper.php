<?php

namespace BarrelStrength\Sprout\mailer\migrations\helpers;

use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\mailer\components\mailers\SystemMailer;
use BarrelStrength\Sprout\mailer\emailthemes\EmailThemeRecord;
use BarrelStrength\Sprout\mailer\MailerModule;
use Craft;
use craft\fieldlayoutelements\TextareaField;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\records\FieldLayout;
use craft\records\FieldLayoutTab;

class MailerSchemaHelper
{
    public const SPROUT_KEY = 'sprout';
    public const DEFAULT_EMAIL_THEME = 'BarrelStrength\Sprout\mailer\components\emailthemes\DefaultEmailTheme';

    public static function insertDefaultMailerSettings(): void
    {
        $mailerSettingsKey = self::SPROUT_KEY . '.' . MailerModule::getInstance()->id . '.mailers.' . StringHelper::UUID();

        Craft::$app->getProjectConfig()->set($mailerSettingsKey, [
            'name' => 'System Mailer',
            'type' => SystemMailer::class,
            'settings' => Json::encode([]),
        ]);
    }

    /**
     */
    public static function createDefaultEmailThemeFieldLayout(): void
    {
        $fieldLayoutRecord = new FieldLayout();
        $fieldLayoutRecord->type = EmailElement::class;
        $fieldLayoutRecord->save();

        $fieldLayoutElement = new TextareaField();
        $fieldLayoutElement->label = 'Body';
        $fieldLayoutElement->attribute = 'defaultBody';
        $fieldLayoutElement->name = 'defaultBody';
        $fieldLayoutElement->rows = 11;
        $fieldLayoutElement->mandatory = true;
        $fieldLayoutElement->required = true;

        $fieldLayoutTabRecord = new FieldLayoutTab();
        $fieldLayoutTabRecord->layoutId = $fieldLayoutRecord->id;
        $fieldLayoutTabRecord->name = Craft::t('sprout-module-mailer', 'Content');
        $fieldLayoutTabRecord->sortOrder = 1;

        $fieldLayoutTabRecord->elements = [
            ['type' => TextareaField::class] + $fieldLayoutElement->toArray(),
        ];
        $fieldLayoutTabRecord->save();

        $emailThemeRecord = new EmailThemeRecord();
        $emailThemeRecord->fieldLayoutId = $fieldLayoutRecord->id;
        $emailThemeRecord->name = Craft::t('sprout-module-mailer', 'Default Theme');
        $emailThemeRecord->type = self::DEFAULT_EMAIL_THEME;
        $emailThemeRecord->htmlEmailTemplatePath = '_email';
        $emailThemeRecord->copyPasteEmailTemplatePath = '_email';

        $emailThemeRecord->save();

        //        $settings = new SettingsRecord();
        //        $settings->siteId = Craft::$app->getSites()->primarySite->id;
        //        $settings->moduleId = 'sprout-module-mailer';
        //        $settings->name = 'defaultEmailTheme.fieldLayoutId';
        //        $settings->settings = $fieldLayoutRecord->id;

    }
}
