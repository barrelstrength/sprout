<?php

namespace BarrelStrength\Sprout\mailer\mailers;

use BarrelStrength\Sprout\datastudio\components\elements\fieldlayoutelements\DataSourceSettingsField;
use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\mailer\components\mailers\fieldlayoutelements\AudienceField;
use BarrelStrength\Sprout\mailer\components\mailers\fieldlayoutelements\ReplyToField;
use BarrelStrength\Sprout\mailer\components\mailers\fieldlayoutelements\SenderField;
use BarrelStrength\Sprout\mailer\components\mailers\fieldlayoutelements\ToField;
use BarrelStrength\Sprout\mailer\components\mailers\SystemMailer;
use BarrelStrength\Sprout\mailer\emailthemes\EmailTheme;
use BarrelStrength\Sprout\mailer\MailerModule;
use Craft;
use craft\base\Component;
use craft\events\DefineFieldLayoutElementsEvent;
use craft\events\DefineFieldLayoutFieldsEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\fieldlayoutelements\Tip;
use craft\helpers\ProjectConfig;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;

class Mailers extends Component
{
    public const EVENT_REGISTER_MAILER_TYPES = 'registerSproutMailerTypes';

    protected array $mailers = [];

    /**
     * @return Mailer[]
     */
    public function getMailerTypes(): array
    {
        $mailers = [];

        $event = new RegisterComponentTypesEvent([
            'types' => $mailers,
        ]);

        $this->trigger(self::EVENT_REGISTER_MAILER_TYPES, $event);

        $eventMailers = $event->types;

        foreach ($eventMailers as $eventMailerClassName) {
            $mailers[$eventMailerClassName] = new $eventMailerClassName();
        }

        return $mailers;
    }

    public function getMailers(): array
    {
        $settings = MailerModule::getInstance()->getSettings();

        $mailerConfigs = ProjectConfig::unpackAssociativeArray($settings->mailers);

        foreach ($mailerConfigs as $uid => $config) {
            $mailers[$uid] = self::getMailerModel($config, $uid);
        }

        return $mailers ?? [];
    }

    public function getMailerByUid(string $uid = null): ?Mailer
    {
        $mailers = $this->getMailers();

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
        $mailers = MailerModule::getInstance()->mailers->getMailers();

        unset($mailers[$uid]);

        if (!self::saveMailers($mailers)) {
            return false;
        }

        return true;
    }

    public static function reorderMailers(array $uids = []): bool
    {
        $oldMailers = MailerModule::getInstance()->mailers->getMailers();
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
            'mailerSettings' => $mailerSettings['settings'],
            'uid' => $uid ?? StringHelper::UUID(),
        ], $settings));

        $mailer->setFieldLayout($fieldLayout);

        return $mailer;
    }

    public static function getDefaultMailer()
    {
        $mailers = MailerModule::getInstance()->mailers->getMailers();

        return reset($mailers);
    }

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
}
