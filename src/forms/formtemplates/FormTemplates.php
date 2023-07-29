<?php

namespace BarrelStrength\Sprout\forms\formtemplates;

use BarrelStrength\Sprout\forms\components\formtemplates\CustomFormTemplateSet;
use BarrelStrength\Sprout\forms\components\formtemplates\DefaultFormTemplateSet;
use craft\events\RegisterComponentTypesEvent;
use yii\base\Component;

class FormTemplates extends Component
{
    public const EVENT_REGISTER_FORM_TEMPLATES = 'registerSproutFormTemplates';

    /**
     * Returns all available Form Templates Class Names
     */
    public function getAllFormTemplateTypes(): array
    {
        $formTemplates = [];
        $formTemplates[] = DefaultFormTemplateSet::class;
        $formTemplates[] = CustomFormTemplateSet::class;

        $event = new RegisterComponentTypesEvent([
            'types' => $formTemplates,
        ]);

        $this->trigger(self::EVENT_REGISTER_FORM_TEMPLATES, $event);

        return $event->types;
    }

    public function getFormTemplateTypesInstances(): array
    {
        $formThemes = $this->getAllFormTemplateTypes();

        $instances = [];
        foreach ($formThemes as $formTheme) {
            $instances[$formTheme::getHandle()] = new $formTheme();
        }

        return $instances;
    }

    /**
     * Returns all available Form Templates
     *
     * @return FormTemplates[]
     */
    public function getAllFormTemplates(): array
    {
        $templateTypes = $this->getAllFormTemplateTypes();
        $templates = [];

        foreach ($templateTypes as $templateType) {
            $templates[$templateType] = new $templateType();
        }

        uasort($templates, static function($a, $b): int {
            /**
             * @var $a FormTemplates
             * @var $b FormTemplates
             */
            return $a::displayName() <=> $b::displayName();
        });

        return $templates;
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
