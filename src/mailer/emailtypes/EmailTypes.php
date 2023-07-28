<?php

namespace BarrelStrength\Sprout\mailer\emailtypes;

use craft\base\Component;
use craft\events\RegisterComponentTypesEvent;

class EmailTypes extends Component
{
    public const EVENT_REGISTER_SPROUT_EMAIL_TYPES = 'registerSproutEmailTypes';

    protected array $_emailTypes = [];

    public function getRegisteredEmailTypes(): array
    {
        $emailTypes = [];

        $event = new RegisterComponentTypesEvent([
            'types' => $emailTypes,
        ]);

        $this->trigger(self::EVENT_REGISTER_SPROUT_EMAIL_TYPES, $event);

        return $event->types;
    }

    /**
     * @return EmailType[]
     */
    public function getEmailTypes(): array
    {
        $registeredEmailTypes = $this->getRegisteredEmailTypes();

        $emailTypes = [];

        foreach ($registeredEmailTypes as $emailType) {
            $emailTypes[$emailType] = new $emailType();
        }

        return $emailTypes;
    }

    public function getEmailTypeByHandle(string $handle = null): ?EmailType
    {
        $emailTypes = $this->getEmailTypes();

        foreach ($emailTypes as $emailType) {
            if ($emailType->handle === $handle) {
                return $emailType;
            }
        }

        return null;
    }
}
