<?php

namespace BarrelStrength\Sprout\mailer\mailers;

use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use craft\base\SavableComponent;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;

abstract class Mailer extends SavableComponent
{
    public ?string $name = null;

    public array $mailerSettings = [];

    public ?string $uid = null;

    public function __toString()
    {
        return self::displayName();
    }

    abstract public function getDescription(): string;

    /**
     * Returns any Field Layout tabs required for the Mailer
     */
    public static function getTabs(FieldLayout $fieldLayout): array
    {
        return [];
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

        return $config;
    }
}
