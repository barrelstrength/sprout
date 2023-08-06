<?php

namespace BarrelStrength\Sprout\mailer\mailers;

use craft\base\Component;
use craft\events\RegisterComponentTypesEvent;

class Mailers extends Component
{
    public const EVENT_REGISTER_MAILERS = 'registerSproutMailers';

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

        $this->trigger(self::EVENT_REGISTER_MAILERS, $event);

        $eventMailers = $event->types;

        foreach ($eventMailers as $eventMailerClassName) {
            $mailers[$eventMailerClassName] = new $eventMailerClassName();
        }

        return $mailers;
    }
}
