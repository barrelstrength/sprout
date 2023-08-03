<?php

namespace BarrelStrength\Sprout\mailer\emailthemes;

use BarrelStrength\Sprout\mailer\components\emailthemes\CustomTemplatesEmailTheme;
use BarrelStrength\Sprout\mailer\components\emailthemes\EmailMessageTheme;
use craft\base\Component;
use craft\events\RegisterComponentTypesEvent;

class EmailThemes extends Component
{
    public const EVENT_REGISTER_SPROUT_EMAIL_THEMES = 'registerSproutEmailThemes';

    public function getEmailThemeTypes(): array
    {
        $emailThemes[] = EmailMessageTheme::class;
        $emailThemes[] = CustomTemplatesEmailTheme::class;

        $event = new RegisterComponentTypesEvent([
            'types' => $emailThemes,
        ]);

        $this->trigger(self::EVENT_REGISTER_SPROUT_EMAIL_THEMES, $event);

        return $event->types;
    }
}
