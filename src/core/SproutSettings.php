<?php

namespace BarrelStrength\Sprout\core;

use BarrelStrength\Sprout\core\modules\SproutModuleTrait;
use Craft;
use craft\config\BaseConfig;

class SproutSettings extends BaseConfig
{
    public const ROOT_PROJECT_CONFIG_KEY = 'sprout';

    public const SITE_TEMPLATE_ROOT = 'sprout';

    public array $modules = [];

    public function modules(array $value): self
    {
        $this->modules = $value;

        return $this;
    }

    public function getCpSettingsRows(): array
    {
        $modules = Sprout::getInstance()->coreModules->getAvailableModules();

        $cpSettingsRows = [];

        foreach ($modules as $module) {
            $projectConfigSettings = $this->modules[$module] ?? null;

            $enabledValue = (isset($projectConfigSettings['enabled']) && !empty($projectConfigSettings['enabled'])) ? $projectConfigSettings['enabled'] : false;

            $enabledInputHtml = Craft::$app->getView()->renderTemplate('_includes/forms/lightswitch.twig', [
                'name' => 'modules[' . $module . '][enabled]',
                'on' => $enabledValue,
                'small' => true,
            ]);

            $infoHtml = '&nbsp;<span class="info">' . $module::getDescription() . '</span>';

            if ($module::hasEditions() && $module::isPro()) {
                $editionHtml = '<span class="sprout-pro">PRO</span>';
            } elseif ($module::hasEditions()) {
                $editionHtml = '<span class="sprout-lite">LITE</span>';
                $editionHtml .= '&nbsp;<span class="info">' . $module::getUpgradeMessage() . '</span>';
            } else {
                $editionHtml = '';
            }

            $cpSettingsRows[$module] = [
                'enabled' => $enabledInputHtml,
                'heading' => $module::getDisplayName() . $infoHtml,
                'edition' => $editionHtml,
            ];
        }

        uksort($cpSettingsRows, static function($a, $b): int {
            /**
             * @var $a SproutModuleTrait
             * @var $b SproutModuleTrait
             */
            return $a::getDisplayName() <=> $b::getDisplayName();
        });

        return $cpSettingsRows;
    }

    public function rules(): array
    {
        $rules = parent::rules();
        $rules[] = [['modules'], 'required'];

        return $rules;
    }
}
