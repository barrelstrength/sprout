<?php

namespace BarrelStrength\Sprout\core\modules;

use BarrelStrength\Sprout\core\Sprout;
use Craft;
use craft\events\RegisterComponentTypesEvent;
use yii\base\Component;

class Modules extends Component
{
    public const INTERNAL_SPROUT_EVENT_REGISTER_AVAILABLE_MODULES = 'registerSproutAvailableModules';

    /**
     * All Sprout modules class names that registered by the active Sprout plugins
     *
     * [
     *   0 => 'BarrelStrength\\Sprout\\forms\\FormsModule',
     *   1 => 'BarrelStrength\\Sprout\\datastudio\\DataStudioModule'
     * ]
     */
    private array $_availableModules = [];

    /**
     * Registers a list of all available Sprout modules
     */
    public function initModules(): void
    {
        $event = new RegisterComponentTypesEvent([
            'types' => [],
        ]);

        $this->trigger(self::INTERNAL_SPROUT_EVENT_REGISTER_AVAILABLE_MODULES, $event);

        $this->_availableModules = array_unique($event->types);
    }

    /**
     * Returns a list of all available Sprout modules class names
     */
    public function getAvailableModules(): array
    {
        if (empty($this->_availableModules)) {
            $this->initModules();
        }

        return $this->_availableModules;
    }

    /**
     * Returns a list of all Sprout modules enabled in the project config
     */
    public function getEnabledModules(): array
    {
        $allModules = Craft::$app->projectConfig->get(Sprout::projectConfigPath('modules')) ?? [];

        $enabledModules = array_keys(array_filter($allModules, static fn($settings) => $settings['enabled'] == true));

        return empty($enabledModules) ? [] : array_unique($enabledModules);
    }
}
