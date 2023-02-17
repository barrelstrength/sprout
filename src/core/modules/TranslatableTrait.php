<?php

namespace BarrelStrength\Sprout\core\modules;

use Craft;
use craft\i18n\PhpMessageSource;

trait TranslatableTrait
{
    /**
     * Registers translations for a given Sprout module
     *
     * @example $this->id => 'sprout-module-forms'
     * @example basePath => ' /var/www/html/vendor/barrelstrength/sprout/src/forms/translations'
     */
    public function registerTranslations(): void
    {
        Craft::$app->i18n->translations[$this->id] = [
            'class' => PhpMessageSource::class,
            'sourceLanguage' => 'en',
            'basePath' => $this->getBasePath() . DIRECTORY_SEPARATOR . 'translations',
            'allowOverrides' => true,
        ];
    }
}
