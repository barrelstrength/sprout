<?php

namespace BarrelStrength\Sprout\mailer\mailers;

use BarrelStrength\Sprout\mailer\MailerModule;
use Craft;
use craft\events\DefineFieldLayoutElementsEvent;
use craft\events\DefineFieldLayoutFieldsEvent;
use craft\helpers\ProjectConfig;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;

class MailerHelper
{
    public const CRAFT_MAILER_SETTINGS = 'craft';

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
                }

                return $element;
            }, $event->elements);

            $newElements = $type::defineNativeElements($event);
            foreach ($newElements as $newElement) {
                if (!in_array($newElement::class, $classNames, true)) {
                    $event->elements[] = $newElement;
                }
            }
        }
    }

    public static function getMailers(): array
    {
        $settings = MailerModule::getInstance()->getSettings();

        $mailerConfigs = ProjectConfig::unpackAssociativeArray($settings->mailers);

        foreach ($mailerConfigs as $uid => $config) {
            $mailers[$uid] = self::getMailerModel($config, $uid);
        }

        return $mailers ?? [];
    }

    public static function getMailerByUid(string $uid = null): ?Mailer
    {
        $mailers = self::getMailers();

        return $mailers[$uid] ?? null;
    }

    public static function saveMailers(array $mailers): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $configPath = MailerModule::projectConfigPath('mailers');
        $mailerConfigs = [];

        foreach ($mailers as $uid => $mailer) {
            $mailerConfigs[$uid] = $mailer->getConfig();
        }

        if (!$projectConfig->set($configPath, ProjectConfig::packAssociativeArray($mailerConfigs))) {
            return false;
        }

        return true;
    }

    public static function removeMailer(string $uid): bool
    {
        $mailers = self::getMailers();

        unset($mailers[$uid]);

        if (!self::saveMailers($mailers)) {
            return false;
        }

        return true;
    }

    public static function reorderMailers(array $uids = []): bool
    {
        $oldMailers = self::getMailers();
        $newMailers = [];

        foreach ($uids as $uid) {
            $newMailers[$uid] = $oldMailers[$uid];
        }

        if (!self::saveMailers($newMailers)) {
            return false;
        }

        return true;
    }

    public static function getMailerModel(array $mailerSettings, string $uid = null): ?Mailer
    {
        $type = $mailerSettings['type'];

        $config = reset($mailerSettings['fieldLayouts']);
        $config['type'] = $type;

        $fieldLayout = FieldLayout::createFromConfig($config);

        $settings = $mailerSettings['settings'] ?? [];

        $mailer = new $type(array_merge([
            'name' => $mailerSettings['name'],
            'mailerSettings' => $mailerSettings['settings'] ?? [],
            'uid' => $uid ?? StringHelper::UUID(),
        ], $settings));

        $mailer->setFieldLayout($fieldLayout);

        return $mailer;
    }

    public static function getDefaultMailer()
    {
        $mailers = self::getMailers();

        return reset($mailers);
    }
}
