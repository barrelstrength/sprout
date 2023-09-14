<?php

namespace BarrelStrength\Sprout\mailer\mailers;

use craft\base\Component;
use craft\events\RegisterComponentTypesEvent;

class Mailers extends Component
{
    public const INTERNAL_SPROUT_EVENT_REGISTER_MAILERS = 'registerInternalSproutMailers';

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

        $this->trigger(self::INTERNAL_SPROUT_EVENT_REGISTER_MAILERS, $event);

        $eventMailers = $event->types;

        foreach ($eventMailers as $eventMailerClassName) {
            $mailers[$eventMailerClassName] = new $eventMailerClassName();
        }

        return $mailers;
    }
}
