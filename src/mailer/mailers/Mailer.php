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

    public ?string $uid = null;

    protected ?FieldLayout $_fieldLayout = null;

    public function __construct($config = [])
    {
        if (isset($config['settings'])) {
            foreach ($config['settings'] as $key => $value) {
                $this->$key = $value;
            }
            unset($config['settings']);
        }

        parent::__construct($config);
    }

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

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['name'], 'required'];

        return $rules;
    }

    public function getConfig(): array
    {
        $config = [
            'name' => $this->name,
            'type' => static::class,
            'uid' => $this->uid,
        ];

        return $config;
    }

    public function prepareMailerInstructionSettingsForEmail(array $settings): array
    {
        return $settings;
    }

    public function prepareMailerInstructionSettingsForDb(array $settings): array
    {
        return $settings;
    }
}
