<?php

namespace BarrelStrength\Sprout\uris;

use BarrelStrength\Sprout\core\modules\SproutModuleTrait;
use BarrelStrength\Sprout\core\modules\TranslatableTrait;
use BarrelStrength\Sprout\core\Sprout;
use BarrelStrength\Sprout\uris\urlenabledsections\UrlEnabledSections;
use Craft;
use yii\base\Module;

/**
 * @property UrlEnabledSections $urlEnabledSections
 */
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

        $this->setComponents([
            'urlEnabledSections' => UrlEnabledSections::class,
        ]);
    }
}
