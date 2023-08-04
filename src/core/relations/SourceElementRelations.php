<?php

namespace BarrelStrength\Sprout\core\relations;

use yii\base\Component;

class SourceElementRelations extends Component
{
    public const EVENT_REGISTER_REVERSE_RELATIONS = 'registerSproutReverseRelations';

    private array $_relations = [];

    public function initReverseRelations(): void
    {
        $event = new SourceElementRelationsEvent([
            'elements' => [],
        ]);

        $this->trigger(self::EVENT_REGISTER_REVERSE_RELATIONS, $event);

        $this->_relations = array_unique($event->sourceElements);
    }

    public function getReverseRelations(): array
    {
        if (empty($this->_relations)) {
            $this->initReverseRelations();
        }

        return $this->_relations;
    }
}
