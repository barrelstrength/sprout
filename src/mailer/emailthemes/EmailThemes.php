<?php

namespace BarrelStrength\Sprout\mailer\emailthemes;

use BarrelStrength\Sprout\mailer\components\emailthemes\CustomEmailTheme;
use BarrelStrength\Sprout\mailer\components\emailthemes\EmailMessageTheme;
use craft\base\Component;
use craft\events\RegisterComponentTypesEvent;

class EmailThemes extends Component
{
    public const EVENT_REGISTER_EMAIL_THEMES = 'registerSproutEmailThemes';

    public function getEmailThemeTypes(): array
    {
        $emailThemes[] = EmailMessageTheme::class;
        $emailThemes[] = CustomEmailTheme::class;

        $event = new RegisterComponentTypesEvent([
            'types' => $emailThemes,
        ]);

        $this->trigger(self::EVENT_REGISTER_EMAIL_THEMES, $event);

        return $event->types;
    }

    public function getEmailThemeTypeInstances(): array
    {
        $emailThemes = $this->getEmailThemeTypes();

        $instances = [];
        foreach ($emailThemes as $emailTheme) {
            $instances[$emailTheme::getHandle()] = new $emailTheme();
        }

        return $instances;
    }

    public function getEmailThemeById($id): ?EmailTheme
    {
        if (!$emailThemeRecord = EmailThemeRecord::findOne($id)) {
            return null;
        }

        $emailTheme = new $emailThemeRecord->type();
        $emailTheme->id = $emailThemeRecord->id;
        $emailTheme->name = $emailThemeRecord->name;
        $emailTheme->htmlEmailTemplate = $emailThemeRecord->htmlEmailTemplate;
        $emailTheme->textEmailTemplate = $emailThemeRecord->textEmailTemplate;
        $emailTheme->copyPasteEmailTemplate = $emailThemeRecord->copyPasteEmailTemplate;

        return $emailTheme;
    }
}
