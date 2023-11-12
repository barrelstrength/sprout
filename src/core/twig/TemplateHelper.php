<?php

namespace BarrelStrength\Sprout\core\twig;

use BarrelStrength\Sprout\core\modules\SproutModuleInterface;
use BarrelStrength\Sprout\core\modules\SproutModuleTrait;
use BarrelStrength\Sprout\core\Sprout;
use BarrelStrength\Sprout\core\SproutSettings;
use Craft;
use craft\config\BaseConfig;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\Html;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\web\View;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use yii\base\Module;

class TemplateHelper
{
    /**
     * Returns the root ID for the Sprout site request Twig templates folder. The value
     * is prefixed with the private template trigger so no files can be accessed directly
     * via front-end requests.
     *
     * This value can be used to include front-end templates provided by Sprout modules
     *
     * @example {% include "sprout/forms/default/form" %}
     */
    public static function getSproutSiteTemplateRoot(): string
    {
        $privateTemplateTrigger = Craft::$app->getConfig()->getGeneral()->privateTemplateTrigger;

        return $privateTemplateTrigger . SproutSettings::SITE_TEMPLATE_ROOT;
    }

    /**
     * Returns an un-cacheable CSRF input tag. Useful to avoid issues
     * where developers may want to, or may accidentally cache forms.
     */
    public static function getDynamicCsrfInput(): string
    {
        $actionUrl = UrlHelper::actionUrl('users/session-info');

        Craft::$app->getView()->registerJs("
            window.Sprout = window.Sprout || {};
            Sprout.sessionInfoActionUrl = '$actionUrl';
        ", View::POS_HEAD);

        Sprout::getInstance()->vite->register('core/DynamicCsrfInput.js');

        return Html::input('hidden', 'SPROUT_CSRF_TOKEN', '');
    }

    /**
     * Returns a URL to a static asset in development or production
     */
    public static function getSproutAssetUrl($uri): string
    {
        if (App::env('SPROUT_VITE_USE_DEV_SERVER')) {
            return App::env('SPROUT_VITE_DEV_SERVER_PUBLIC') . 'static' . DIRECTORY_SEPARATOR . $uri;
        }

        $baseUrl = Craft::$app->getView()->getAssetManager()->getPublishedUrl('@Sprout/Assets/dist/static');

        return $baseUrl . DIRECTORY_SEPARATOR . $uri;
    }

    /**
     * Returns an array that can be used in a multiselect field from an array of component types
     */
    public static function optionsFromComponentTypes($savableComponentTypes, string $firstLabel = null): array
    {
        $options = [];

        if ($firstLabel) {
            $options[] = [
                'label' => $firstLabel,
                'value' => '',
            ];
        }
        foreach ($savableComponentTypes as $savableComponentType) {
            $label = $savableComponentType::displayName();
            $value = $savableComponentType;
            $options[] = [
                'label' => $label,
                'value' => $value,
            ];
        }

        return $options;
    }

    public static function getConfigWarning(SproutModuleInterface $sproutModule, string $setting): string
    {
        $module = new $sproutModule($sproutModule::class);

        $settings = $module->createSettingsModel();

        $envConfig = App::envConfig($settings::class, $module::getEnvPrefix());
        $fileConfigSettings = Craft::$app->getConfig()->getConfigFromFile($module::getModuleId());

        $params = [
            'setting' => Html::tag('b', $setting),
            'configFile' => Html::a('config/' . $module::getModuleId() . '.php',
                UrlHelper::cpUrl('sprout/settings/preview/' . $module::getModuleId() . '.php'), [
                    'target' => '_blank',
                    'style' => 'color: inherit;',
                ]),

        ];

        $envVariable = $module::getEnvPrefix() . strtoupper(StringHelper::toSnakeCase($setting));
        $envParams = [
            'setting' => Html::tag('b', $envVariable),
            'envFile' => Html::tag('code', '.env'),
        ];

        if ($fileConfigSettings instanceof BaseConfig) {
            return Craft::t('sprout-module-core', 'This setting is defined by the override values or config object defaults in {configFile}.', $params);
        }

        if (isset($fileConfigSettings[$setting])) {
            return Craft::t('sprout-module-core', 'This setting is defined by the {setting} value in {configFile}', $params);
        }

        if (isset($envConfig[$setting])) {
            return Craft::t('sprout-module-core', 'This setting is defined by the {setting} value in the {envFile} file', $envParams);
        }

        return '';
    }

    public static function getTemplateFolderSuggestions(): array
    {
        // Get all the template files sorted by path length
        $roots = ArrayHelper::merge([
            '' => [Craft::$app->getPath()->getSiteTemplatesPath()],
        ], Craft::$app->getView()->getSiteTemplateRoots());

        $suggestions = [];
        $templates = [];
        $sites = [];

        foreach (Craft::$app->getSites()->getAllSites() as $site) {
            $sites[$site->handle] = Craft::t('site', $site->getName());
        }

        foreach ($roots as $root => $basePaths) {
            foreach ($basePaths as $basePath) {
                if (!is_dir($basePath)) {
                    continue;
                }

                $directory = new RecursiveDirectoryIterator($basePath);

                $filter = new RecursiveCallbackFilterIterator($directory, function($current) {
                    // Skip hidden files and directories, as well as node_modules/ folders
                    return !($current->getFilename()[0] === '..' || $current->getFilename() === 'node_modules');
                });

                $iterator = new RecursiveIteratorIterator($filter);

                $dirs = [];
                $pathLengths = [];

                foreach ($iterator as $file) {
                    /** @var SplFileInfo $file */
                    if ($file->isDir()) {
                        $dirs[] = $file;
                        $pathLengths[] = strlen($file->getRealPath());
                    }
                }

                array_multisort($pathLengths, SORT_NUMERIC, $dirs);

                $basePathLength = strlen($basePath);

                foreach ($dirs as $file) {
                    $template = substr($file->getRealPath(), $basePathLength + 1);
                    $hint = null;

                    // Is it in a site template directory?
                    foreach ($sites as $handle => $name) {
                        if (str_starts_with($template, $handle . DIRECTORY_SEPARATOR)) {
                            $hint = $name;
                            $template = substr($template, strlen($handle) + 1);
                            break;
                        }
                    }

                    // Prepend the template root path
                    if ($root !== '') {
                        $template = sprintf('%s/%s', $root, $template);
                    }

                    // Avoid listing the same template path twice (considering localized templates)
                    if (isset($templates[$template])) {
                        continue;
                    }

                    $templates[$template] = true;
                    $suggestions[] = [
                        'name' => $template,
                        'hint' => $hint,
                    ];
                }
            }
        }

        $suggestions = array_filter($suggestions, static function($folder) {
            return $folder['name'] !== '';
        });

        ArrayHelper::multisort($suggestions, 'name');

        return [
            [
                'label' => Craft::t('app', 'Templates'),
                'data' => $suggestions,
            ],
        ];
    }
}
