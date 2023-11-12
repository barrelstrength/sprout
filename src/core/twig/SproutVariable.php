<?php

namespace BarrelStrength\Sprout\core\twig;

use BarrelStrength\Sprout\core\modules\SproutModuleInterface;
use BarrelStrength\Sprout\core\Sprout;
use BarrelStrength\Sprout\datastudio\datasets\TwigDataSetVariable;
use BarrelStrength\Sprout\datastudio\DataStudioModule;
use BarrelStrength\Sprout\forms\forms\FormsVariable;
use BarrelStrength\Sprout\forms\FormsModule;
use BarrelStrength\Sprout\mailer\MailerModule;
use BarrelStrength\Sprout\mailer\twig\MailerVariable;
use BarrelStrength\Sprout\meta\metadata\MetadataVariable;
use BarrelStrength\Sprout\meta\MetaModule;
use BarrelStrength\Sprout\redirects\RedirectsModule;
use BarrelStrength\Sprout\sentemail\SentEmailModule;
use BarrelStrength\Sprout\sitemaps\SitemapsModule;
use BarrelStrength\Sprout\transactional\TransactionalModule;
use craft\helpers\StringHelper;
use yii\base\Module;
use yii\di\ServiceLocator;

/**
 * These properties are used for Twig autocomplete support
 *
 * @property FormsVariable $forms
 * @property MetadataVariable $meta
 * @property MailerVariable $subscriberLists
 * @property TwigDataSetVariable $twigDataSet
 */
class SproutVariable extends ServiceLocator
{
    public const EVENT_INIT = 'init';

    /**
     * This values don't get autocompleted with PhpStorm
     *
     * @property Sprout $core
     * @property FormsModule $forms
     * @property MailerModule $mailer
     * @property MetaModule $meta
     * @property TransactionalModule $notifications
     * @property RedirectsModule $redirects
     * @property DataStudioModule $dataStudio
     * @property SentEmailModule $sentEmail
     * @property SitemapsModule $sitemaps
     * @property ViteVariable $vite
     */
    public array $modules;

    public function init(): void
    {
        parent::init();

        if ($this->hasEventHandlers(self::EVENT_INIT)) {
            $this->trigger(self::EVENT_INIT);
        }
    }

    /**
     * Makes a module instance available to templates via the `sprout.modules` variable.
     *
     * Example:
     * sprout.modules.[moduleShortName]
     */
    public function registerModule(SproutModuleInterface $module): void
    {
        $this->modules[StringHelper::toCamelCase($module::getShortName())] = $module;
    }

    /**
     * Makes a variable available to templates via `sprout.[variableName]`
     *
     * Example:
     * sprout.forms.displayForm()
     */
    public function registerVariable($name, $class): void
    {
        $this->set($name, new $class());
    }
}
