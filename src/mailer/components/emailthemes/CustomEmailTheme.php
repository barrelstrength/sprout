<?php

namespace BarrelStrength\Sprout\mailer\components\emailthemes;

use BarrelStrength\Sprout\mailer\emailthemes\EmailTheme;
use Craft;
use craft\web\View;

class CustomEmailTheme extends EmailTheme
{
    /**
     * Handle will be defined in settings
     */
    public ?string $handle = '';

    public static function displayName(): string
    {
        return Craft::t('sprout-module-mailer', 'Custom Theme');
    }

    public function getTemplateMode(): string
    {
        return View::TEMPLATE_MODE_SITE;
    }

    public function getTemplateRoot(): string
    {
        return Craft::$app->path->getSiteTemplatesPath();
    }
}



