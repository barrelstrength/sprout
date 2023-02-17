<?php

namespace BarrelStrength\Sprout\datastudio\components\datasources;

use BarrelStrength\Sprout\datastudio\components\elements\DataSetElement;
use BarrelStrength\Sprout\datastudio\datasources\DataSource;
use Craft;
use Exception;

class CustomQueryDataSource extends DataSource
{
    /**
     * The custom query that will be run for this report
     */
    public ?string $query = null;

    public static function getHandle(): string
    {
        return 'custom-query';
    }

    public static function displayName(): string
    {
        return Craft::t('sprout-module-data-studio', 'Custom Query');
    }

    public function getDescription(): string
    {
        return Craft::t('sprout-module-data-studio', 'Create data sets using a custom database query');
    }

    public function isAllowHtmlEditable(): bool
    {
        return true;
    }

    public function getResults(DataSetElement $dataSet): array
    {
        $result = [];

        try {
            $result = Craft::$app->getDb()->createCommand($this->query)->queryAll();
        } catch (Exception $exception) {
            $dataSet->addError('query-error', $exception->getMessage());
        }

        return $result;
    }

    public function getSettingsHtml(): ?string
    {
        $settingsErrors = $this->dataSet->getErrors('settings');
        $settingsErrors = array_shift($settingsErrors);

        return Craft::$app->getView()->renderTemplate('sprout-module-data-studio/_components/datasources/CustomQuery/settings', [
            'settings' => $this->dataSet->getDataSource()->getSettings(),
            'errors' => $settingsErrors,
        ]);
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = ['query', 'validateQuery'];

        return $rules;
    }

    public function validateQuery($attribute): void
    {
        if (empty($this->query)) {
            $this->addError($attribute, Craft::t('sprout-module-data-studio', 'Query cannot be blank.'));
        }
    }
}
