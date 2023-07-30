<?php

namespace BarrelStrength\Sprout\forms\formthemes;

use BarrelStrength\Sprout\forms\components\formthemes\CustomFormTheme;
use BarrelStrength\Sprout\forms\components\formthemes\DefaultFormTheme;
use craft\events\RegisterComponentTypesEvent;
use yii\base\Component;

class FormThemes extends Component
{
    public const EVENT_REGISTER_SPROUT_FORM_THEMES = 'registerSproutFormThemes';

    public function getFormThemeTypes(): array
    {
        $formThemes = [];
        $formThemes[] = DefaultFormTheme::class;
        $formThemes[] = CustomFormTheme::class;

        $event = new RegisterComponentTypesEvent([
            'types' => $formThemes,
        ]);

        $this->trigger(self::EVENT_REGISTER_SPROUT_FORM_THEMES, $event);

        return $event->types;
    }

    public function getFormThemeTypeInstances(): array
    {
        $formThemeTypes = $this->getFormThemeTypes();
        $instances = [];
        foreach ($formThemeTypes as $formThemeType) {
            $instances[$formThemeType] = new $formThemeType();
        }

        return $instances;
    }

    public function getFormThemeOptions(): array
    {
        $themes = FormThemeHelper::getFormThemes();

        return array_map(static function($theme) {
            return [
                'label' => $theme->name,
                'value' => $theme->uid,
            ];
        }, $themes);
    }
}
