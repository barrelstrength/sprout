<?php

namespace BarrelStrength\Sprout\forms\formtypes;

use BarrelStrength\Sprout\forms\components\formtypes\CustomTemplatesFormType;
use BarrelStrength\Sprout\forms\components\formtypes\DefaultFormType;
use craft\events\RegisterComponentTypesEvent;
use yii\base\Component;

class FormTypes extends Component
{
    public const EVENT_REGISTER_FORM_TYPES = 'registerSproutFormTypes';

    /**
     * @return FormType[]
     */
    public function getFormTypeTypes(): array
    {
        $formTypes[] = DefaultFormType::class;
        $formTypes[] = CustomTemplatesFormType::class;

        $event = new RegisterComponentTypesEvent([
            'types' => $formTypes,
        ]);

        $this->trigger(self::EVENT_REGISTER_FORM_TYPES, $event);

        return $event->types;
    }

    public function getFormTypeOptions(): array
    {
        $formTypes = FormTypeHelper::getFormTypes();

        return array_map(static function($formType) {
            return [
                'label' => $formType->name,
                'value' => $formType->uid,
            ];
        }, $formTypes);
    }
}
