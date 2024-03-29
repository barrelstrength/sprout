<?php

namespace BarrelStrength\Sprout\fields;

use BarrelStrength\Sprout\core\modules\SproutModuleInterface;
use BarrelStrength\Sprout\core\modules\SproutModuleTrait;
use BarrelStrength\Sprout\core\modules\TranslatableTrait;
use BarrelStrength\Sprout\core\Sprout;
use BarrelStrength\Sprout\fields\helpers\PhoneHelper;
use Craft;
use craft\events\RegisterTemplateRootsEvent;
use craft\web\View;
use yii\base\Event;
use yii\base\Module;

/**
 * @property PhoneHelper $phoneHelper
 */
class FieldsModule extends Module implements SproutModuleInterface
{
    use SproutModuleTrait;
    use TranslatableTrait;

    public static function getInstance(): FieldsModule
    {
        /** @var FieldsModule $module */
        $module = Sprout::getSproutModule(static::class, 'sprout-module-fields');

        return $module;
    }

    public static function getDisplayName(): string
    {
        $displayName = Craft::t('sprout-module-core', 'Fields');

        return $displayName;
    }

    public static function getShortName(): string
    {
        return 'fields';
    }

    public function init(): void
    {
        parent::init();

        $this->setComponents([
            'phoneHelper' => PhoneHelper::class,
        ]);

        $this->registerTranslations();

        Event::on(
            View::class,
            View::EVENT_REGISTER_CP_TEMPLATE_ROOTS,
            function(RegisterTemplateRootsEvent $e): void {
                $e->roots['sprout-module-fields'] = $this->getBasePath() . DIRECTORY_SEPARATOR . 'templates';
            });
    }
}
