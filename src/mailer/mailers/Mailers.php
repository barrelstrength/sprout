<?php

namespace BarrelStrength\Sprout\mailer\mailers;

use BarrelStrength\Sprout\mailer\MailerModule;
use Craft;
use craft\base\Component;
use craft\events\ConfigEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\helpers\Json;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use Throwable;
use yii\base\Exception;

class Mailers extends Component
{
    public const EVENT_REGISTER_MAILER_TYPES = 'registerSproutMailerTypes';

    protected array $mailers = [];

    /**
     * @return Mailer[]
     */
    public function getRegisteredMailers(): array
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

        $mailers = $settings->mailers;

        // @todo - Update to use project config
        foreach ($mailers as $uid => $mailer) {
            $mailers[$uid] = $mailer;
        }

        return $mailers;
    }

    public function getMailerByUid(string $uid): ?Mailer
    {
        $mailers = $this->getMailers();

        return $mailers[$uid] ?? null;
    }
}
