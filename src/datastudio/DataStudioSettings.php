<?php

namespace BarrelStrength\Sprout\datastudio;

use BarrelStrength\Sprout\datastudio\components\elements\DataSetElement;
use Craft;
use craft\config\BaseConfig;
use craft\models\FieldLayout;

class DataStudioSettings extends BaseConfig
{
    public int $defaultPageLength = 10;

    public string $defaultExportDelimiter = ',';

    /**
     * The Field Layout Config that will be saved to Project Config
     */
    public array $fieldLayouts = [];

    public function defaultPageLength(int $value): self
    {
        $this->defaultPageLength = $value;

        return $this;
    }

    public function defaultExportDelimiter(string $value): self
    {
        $this->defaultExportDelimiter = $value;

        return $this;
    }

    public function getFieldLayout(): FieldLayout
    {
        $fieldLayouts = Craft::$app->getProjectConfig()->get(DataStudioModule::projectConfigPath('fieldLayouts')) ?? [];

        // If there is a field layout, it's saved with a UID key and we just need the first value
        if ($fieldLayout = reset($fieldLayouts)) {
            return FieldLayout::createFromConfig($fieldLayout);
        }

        return new FieldLayout([
            'type' => DataSetElement::class,
        ]);
    }
}
