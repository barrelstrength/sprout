<?php

namespace BarrelStrength\Sprout\uris;

use BarrelStrength\Sprout\core\modules\SproutModuleInterface;
use BarrelStrength\Sprout\core\modules\SproutModuleTrait;
use BarrelStrength\Sprout\core\modules\TranslatableTrait;
use BarrelStrength\Sprout\core\Sprout;
use BarrelStrength\Sprout\core\twig\SproutVariable;
use BarrelStrength\Sprout\uris\links\Links;
use Craft;
use craft\events\RegisterTemplateRootsEvent;
use craft\web\View;
use yii\base\Event;
use yii\base\Module;

/**
 * @property Links $links
 */
class UrisModule extends Module implements SproutModuleInterface
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

        $this->setComponents([
            'links' => Links::class,
        ]);

        Event::on(
            View::class,
            View::EVENT_REGISTER_CP_TEMPLATE_ROOTS,
            function(RegisterTemplateRootsEvent $e): void {
                $e->roots['sprout-module-uris'] = $this->getBasePath() . DIRECTORY_SEPARATOR . 'templates';
            });

        Event::on(
            SproutVariable::class,
            SproutVariable::EVENT_INIT,
            function(Event $event): void {
                $event->sender->registerModule($this);
            });
    }
}
