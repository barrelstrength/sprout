<?php

namespace BarrelStrength\Sprout\uris;

use BarrelStrength\Sprout\core\modules\SproutModuleTrait;
use BarrelStrength\Sprout\core\modules\TranslatableTrait;
use BarrelStrength\Sprout\core\Sprout;
use BarrelStrength\Sprout\uris\components\elements\CategoryElementGroupBehavior;
use BarrelStrength\Sprout\uris\components\elements\EntryElementGroupBehavior;
use BarrelStrength\Sprout\uris\components\elements\ProductElementGroupBehavior;
use Craft;
use craft\base\Element;
use craft\commerce\elements\Product;
use craft\elements\Category;
use craft\elements\Entry;
use craft\events\DefineBehaviorsEvent;
use yii\base\Event;
use yii\base\Module;

class UrisModule extends Module
{
    use SproutModuleTrait;
    use TranslatableTrait;

    public static function getInstance(): UrisModule
    {
        /** @var UrisModule $module */
        $module = Sprout::getSproutModule(static::class, 'sprout-module-uris');

        return $module;
    }

    public static function getDisplayName(): string
    {
        $displayName = Craft::t('sprout-module-core', 'URIs');

        return $displayName;
    }

    public static function getShortName(): string
    {
        return 'uris';
    }

    public function init(): void
    {
        parent::init();

        $this->registerTranslations();

        Event::on(
            Element::class,
            Element::EVENT_DEFINE_BEHAVIORS,
            [self::class, 'attachElementBehaviors']
        );
    }

    public static function getElementsWithUris(): array
    {
        /** @var Element[] $types */
        $types = Craft::$app->getElements()->getAllElementTypes();

        $uriTypes = [];

        foreach ($types as $type) {
            if (!$type::hasUris()) {
                continue;
            }

            $uriTypes[] = $type;
        }

        return $uriTypes;
    }

    public static function attachElementBehaviors(DefineBehaviorsEvent $event): void
    {
        /** @var Element $element */
        $element = $event->sender;

        if ($element instanceof Entry) {
            $event->behaviors[EntryElementGroupBehavior::class] = EntryElementGroupBehavior::class;
        }

        if ($element instanceof Category) {
            $event->behaviors[CategoryElementGroupBehavior::class] = CategoryElementGroupBehavior::class;
        }

        if ($element instanceof Product) {
            $event->behaviors[ProductElementGroupBehavior::class] = ProductElementGroupBehavior::class;
        }
    }
}
