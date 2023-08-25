<?php

namespace BarrelStrength\Sprout\mailer\emailtypes;

use BarrelStrength\Sprout\mailer\components\emailtypes\CustomTemplatesEmailType;
use BarrelStrength\Sprout\mailer\components\emailtypes\EmailMessageEmailType;
use craft\base\Component;
use craft\events\RegisterComponentTypesEvent;

class EmailTypes extends Component
{
    public const EVENT_REGISTER_EMAIL_TYPES = 'registerSproutEmailTypes';

    public function getEmailTypeTypes(): array
    {
        $emailTypes[] = EmailMessageEmailType::class;
        $emailTypes[] = CustomTemplatesEmailType::class;

        $event = new RegisterComponentTypesEvent([
            'types' => $emailTypes,
        ]);

        $this->trigger(self::EVENT_REGISTER_EMAIL_TYPES, $event);

        return $event->types;
    }
}
