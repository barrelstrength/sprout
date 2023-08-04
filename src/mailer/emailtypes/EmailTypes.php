<?php

namespace BarrelStrength\Sprout\mailer\emailtypes;

use BarrelStrength\Sprout\core\helpers\ComponentHelper;
use craft\base\Component;
use craft\events\RegisterComponentTypesEvent;

class EmailTypes extends Component
{
    public const EVENT_REGISTER_EMAIL_TYPES = 'registerSproutEmailTypes';

    protected array $_emailTypeTypes = [];

    /**
     * @return EmailType[]
     */
    public function getEmailTypeTypes(): array
    {
        $emailTypes = [];

        $event = new RegisterComponentTypesEvent([
            'types' => $emailTypes,
        ]);

        $this->trigger(self::EVENT_REGISTER_EMAIL_TYPES, $event);

        foreach ($event->types as $emailType) {
            $emailTypes[$emailType] = $emailType;
        }

        return $emailTypes;
    }

    public function getEmailTypeByHandle(string $handle = null): ?EmailType
    {
        $emailTypeTypes = $this->getEmailTypeTypes();
        $emailTypes = ComponentHelper::typesToInstances($emailTypeTypes);

        foreach ($emailTypes as $emailType) {
            if ($emailType->handle === $handle) {
                return $emailType;
            }
        }

        return null;
    }
}
