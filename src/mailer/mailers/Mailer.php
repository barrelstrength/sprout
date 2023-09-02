<?php

namespace BarrelStrength\Sprout\mailer\mailers;

use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use craft\base\SavableComponent;
use craft\events\DefineFieldLayoutElementsEvent;
use craft\events\DefineFieldLayoutFieldsEvent;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;

abstract class Mailer extends SavableComponent implements MailerInterface
{
    public ?string $name = null;

    public array $mailerSettings = [];

    public ?string $uid = null;

    protected ?FieldLayout $_fieldLayout = null;

    public function __toString()
    {
        return self::displayName();
    }

    abstract public function getDescription(): string;

    public static function defineNativeFields(DefineFieldLayoutFieldsEvent $event): array
    {
        return [];
    }

    public static function defineNativeElements(DefineFieldLayoutElementsEvent $event): array
    {
        return [];
    }

    public function createFieldLayout(): ?FieldLayout
    {
        return null;
    }

    public function getFieldLayout(): FieldLayout
    {
        if ($this->_fieldLayout) {
            return $this->_fieldLayout;
        }

        $fieldLayout = $this->createFieldLayout();

        return $this->_fieldLayout = $fieldLayout;
    }

    public function setFieldLayout(?FieldLayout $fieldLayout): void
    {
        $this->_fieldLayout = $fieldLayout;
    }

    /**
     * Returns the URL for this Mailer's CP Settings
     */
    final public function getCpEditUrl(): ?string
    {
        return UrlHelper::cpUrl('sprout/settings/mailers/edit/' . $this->uid);
    }

    /**
     * Returns the settings for this mailer
     */
    public function getSettings(): array
    {
        return [];
    }

    /**
     * Returns a rendered html string to use for capturing settings input
     */
    public function getSettingsHtml(): ?string
    {
        return '';
    }

    abstract public function createMailerInstructionsSettingsModel(): MailerInstructionsInterface;

    public function createMailerInstructionsTestSettingsModel(): MailerInstructionsInterface
    {
        return $this->createMailerInstructionsSettingsModel();
    }

    abstract public function send(EmailElement $email, MailerInstructionsInterface $mailerInstructionsSettings): void;

    public function getConfig(): array
    {
        $config = [
            'name' => $this->name,
            'type' => static::class,
            'settings' => $this->mailerSettings,
        ];

        $fieldLayout = $this->getFieldLayout();

        if ($fieldLayoutConfig = $fieldLayout->getConfig()) {
            $config['fieldLayouts'] = [
                $fieldLayout->uid => $fieldLayoutConfig,
            ];
        }

        return $config;
    }
}
