<?php

namespace BarrelStrength\Sprout\core\relations;

use Craft;
use craft\base\Element;
use craft\commerce\elements\Product;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\Tag;
use craft\elements\User;
use craft\events\RegisterComponentTypesEvent;
use yii\base\Event;

class RelationsHelper
{
    public const EVENT_REGISTER_SOURCE_RELATIONS_ELEMENT_TYPES = 'registerSproutSourceRelationsElementTypes';
    public const EVENT_ADD_SOURCE_ELEMENT_RELATIONS = 'registerSproutAddSourceElementRelations';

    /**
     * @todo - create a setting that can be reused in different plugins
     * that allows a user or code request getting relations to define
     * which elements should be included in the response
     */
    public static function getSourceElementRelations(Element $element, array $excludeSourceElementTypes = [], array $onlySourceElementTypes = []): array
    {
        $relations = [];

        $elementTypes = [
            Asset::class,
            Entry::class,
            Category::class,
            Tag::class,
            User::class,
        ];

        if (Craft::$app->getPlugins()->isPluginInstalled('commerce')) {
            $elementTypes[] = Product::class;
        }

        $event = new RegisterComponentTypesEvent([
            'types' => [],
        ]);
        Event::trigger(static::class, self::EVENT_REGISTER_SOURCE_RELATIONS_ELEMENT_TYPES, $event);

        $elementTypes = array_merge($elementTypes, $event->types);

        if ($excludeSourceElementTypes) {
            $elementTypes = array_diff($elementTypes, $excludeSourceElementTypes);
        }

        // Overwrite default with provided Element Type list
        if ($onlySourceElementTypes) {
            $elementTypes = $onlySourceElementTypes;
        }

        foreach ($elementTypes as $elementType) {
            $query = $elementType::find();
            $query->relatedTo($element);
            $query->anyStatus();
            $relations[] = $query->all();
        }

        $event = new SourceElementRelationsEvent([
            'targetElement' => $element,
            'sourceElements' => [],
        ]);
        Event::trigger(static::class, self::EVENT_ADD_SOURCE_ELEMENT_RELATIONS, $event);

        $elements = array_merge([], ...$relations, ...$event->sourceElements);

        return array_map(static function($element) {
            return [
                'elementId' => $element->id,
                'name' => $element->title,
                'cpEditUrl' => $element->getCpEditUrl(),
                'type' => $element::displayName(),
                'actionUrl' => $element->getCpEditUrl(),
            ];
        }, $elements);
    }

    public static function getElementRelationsById($elementId, array $excludeSourceElementTypes = [], array $onlySourceElementTypes = []): array
    {
        $element = Craft::$app->getElements()->getElementById($elementId);

        if (!$element) {
            return [];
        }

        return self::getSourceElementRelations($element, $excludeSourceElementTypes, $onlySourceElementTypes);
    }
}
