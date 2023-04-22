<?php

namespace BarrelStrength\Sprout\datastudio\components\datasources;

use BarrelStrength\Sprout\core\twig\TemplateHelper;
use BarrelStrength\Sprout\datastudio\components\elements\DataSetElement;
use BarrelStrength\Sprout\datastudio\datasources\DataSource;
use BarrelStrength\Sprout\datastudio\DataStudioModule;
use Craft;
use craft\helpers\DateTimeHelper;
use craft\web\View;

class CustomTwigTemplateQueryDataSource extends DataSource
{
    /**
     * The template that defines how the results array is populated for this Data Source
     */
    public ?string $resultsTemplate = null;

    /**
     * The template that defines the settings a user can interact for this Data Source
     */
    public ?string $settingsTemplate = null;

    /**
     * Dynamic array of all settings defined in $this->settingsTemplate
     */
    protected array $templateSettings = [];

    public function __construct($config = [])
    {
        foreach ($config as $setting => $value) {
            if (in_array($setting, ['resultsTemplate', 'settingsTemplate'])) {
                continue;
            }

            // Date time field
            if (strpos($setting, 'datetime') === 0) {
                $value = DateTimeHelper::toDateTime($value);
            }

            $this->templateSettings[$setting] = $value;
            unset ($config[$setting]);
        }

        parent::__construct($config);
    }

    public static function getHandle(): string
    {
        return 'custom-twig-template-query';
    }

    public static function displayName(): string
    {
        return Craft::t('sprout-module-data-studio', 'Twig Template Query');
    }

    public function getDescription(): string
    {
        return Craft::t('sprout-module-data-studio', 'Create a data set using Twig in your templates folder.');
    }

    public function isAllowHtmlEditable(): bool
    {
        return true;
    }

    public function getDefaultLabels(DataSetElement $dataSet): array
    {
        if (!DataStudioModule::getInstance()->customTwigTemplates->hasRun) {
            $this->processFrontEndResultsTemplate();
            DataStudioModule::getInstance()->customTwigTemplates->hasRun = true;
        }

        $labels = DataStudioModule::getInstance()->customTwigTemplates->labels;

        if ($labels !== []) {
            return $labels;
        }

        return [];
    }

    public function getResults(DataSetElement $dataSet): array
    {
        if (!DataStudioModule::getInstance()->customTwigTemplates->hasRun) {
            $this->processFrontEndResultsTemplate();
            DataStudioModule::getInstance()->customTwigTemplates->hasRun = true;
        }

        $rows = DataStudioModule::getInstance()->customTwigTemplates->rows;

        $this->processHeaderRow($rows);

        if (is_countable($rows) ? count($rows) : 0) {
            return $rows;
        }

        return [];
    }

    public function getSettingsHtml(): ?string
    {
        $settingsErrors = $this->getErrors('templateSettings');
        $settingsErrors = array_shift($settingsErrors);

        $customSettingsHtml = null;

        // If settings template exists as setting, look for it on the front-end.
        // If not, return a nice message explain how to handle settings.
        if ($this->settingsTemplate) {

            if ($sproutExamplePath = $this->getSproutExampleTemplatePath($this->settingsTemplate)) {
                // Sprout Example Templates
                $customSettingsTemplatePath = $sproutExamplePath;
            } else {
                // Standard Templates
                $customSettingsTemplatePath = Craft::$app->getPath()->getSiteTemplatesPath() . DIRECTORY_SEPARATOR . $this->settingsTemplate;
            }

            $customSettingsFileContent = file_get_contents($customSettingsTemplatePath);

            if (!empty($customSettingsFileContent)) {
                // Add support for processing Template Settings by including Craft CP Form Macros and
                // wrapping all settings fields in the `settings` namespace
                $customSettingsHtmlWithExtras = $customSettingsFileContent;

                $customSettingsHtml = Craft::$app->getView()->renderString($customSettingsHtmlWithExtras, [
                    'settings' => array_merge($this->getSettings(), $this->templateSettings),
                    'errors' => $settingsErrors,
                ], View::TEMPLATE_MODE_CP);
            }
        }

        return Craft::$app->getView()->renderTemplate('sprout-module-data-studio/_components/datasources/CustomTwigTemplate/settings', [
            'settings' => $this->getSettings(),
            'errors' => $settingsErrors,
            'settingsContents' => $customSettingsHtml ?? null,
        ]);
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = ['resultsTemplate', 'validateResultsTemplate'];

        return $rules;
    }

    public function validateResultsTemplate($attribute): void
    {
        if (!$this->resultsTemplate) {
            $this->addError($attribute, Craft::t('sprout-module-data-studio', 'Results template cannot be blank.'));
        }
    }

    /**
     * Make sure we only process our template once.
     * Since we need data from the template in both the getDefaultLabels and getResults
     * methods we have to check in both places
     */
    public function processFrontEndResultsTemplate(): void
    {
        $view = Craft::$app->getView();

        $view->setTemplateMode(View::TEMPLATE_MODE_SITE);

        $view->renderTemplate($this->resultsTemplate, [
            'isExport' => $this->isExport,
            'settings' => array_merge($this->getSettings(), $this->templateSettings),
        ]);

        $view->setTemplateMode(View::TEMPLATE_MODE_CP);
    }

    public function processHeaderRow(&$rows): void
    {
        $labels = DataStudioModule::getInstance()->customTwigTemplates->labels;

        // If we don't have default labels, we will use the first row as for our column headers
        // We do so by making the first row the keys of the second row
        if (empty($labels) && (is_countable($rows) ? count($rows) : 0)) {
            $headerRow = [];

            /**
             * @var $firstRowColumns array
             */
            $firstRowColumns = array_shift($rows);

            if (is_countable($firstRowColumns) ? count($firstRowColumns) : 0) {
                $secondRow = array_shift($rows);

                foreach ($firstRowColumns as $key => $column) {
                    $headerRow[$column] = $secondRow[$key];
                }
            }

            array_unshift($rows, $headerRow);
        }
    }

    /**
     * If the template starts with the getSproutSiteTemplateRoot() value, we need to
     * update it to use the filepath in our module instead of the front end
     * (because we allow the use of Craft's CP '_include/forms'
     */
    protected function getSproutExampleTemplatePath($template): ?string
    {
        $sproutExampleTemplateRoot = TemplateHelper::getSproutSiteTemplateRoot() . DIRECTORY_SEPARATOR . 'examples';

        if (!str_starts_with($template, $sproutExampleTemplateRoot)) {
            return null;
        }

        // Strip off the template root prefix and leave 'examples/' in the template path
        $sproutTemplatePath = ltrim($template, TemplateHelper::getSproutSiteTemplateRoot());

        return Craft::getAlias('@Sprout/TemplatePath') . DIRECTORY_SEPARATOR . $sproutTemplatePath;
    }
}
