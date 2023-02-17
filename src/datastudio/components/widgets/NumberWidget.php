<?php

namespace BarrelStrength\Sprout\datastudio\components\widgets;

use BarrelStrength\Sprout\datastudio\components\elements\DataSetElement;
use BarrelStrength\Sprout\datastudio\DataStudioModule;
use Craft;
use craft\base\Widget;
use Exception;

class NumberWidget extends Widget
{
    public ?string $heading = null;

    public ?string $description = null;

    public ?int $number = null;

    public ?string $resultPrefix = null;

    public ?int $dataSetId = null;

    public static function displayName(): string
    {
        return Craft::t('sprout-module-data-studio', 'Number');
    }

    public static function icon(): null|string
    {
        return Craft::getAlias('@Sprout/Assets/dist/static/data-studio/icons/icon-mask.svg');
    }

    public function getTitle(): ?string
    {
        return $this->heading;
    }

    public function getSettingsHtml(): ?string
    {
        $dataSetOptions = DataStudioModule::getInstance()->dataSets->getDataSetAsSelectFieldOptions();

        return Craft::$app->getView()->renderTemplate('sprout-module-data-studio/_components/widgets/Number/settings', [
                'widget' => $this,
                'dataSetOptions' => $dataSetOptions,
            ]
        );
    }

    public function getBodyHtml(): ?string
    {
        /** @var DataSetElement $dataSet */
        $dataSet = Craft::$app->elements->getElementById($this->dataSetId, DataSetElement::class);

        if ($dataSet) {
            $dataSource = new $dataSet->type();

            if ($dataSource) {
                try {
                    $result = $dataSource->getResults($dataSet);

                    return Craft::$app->getView()->renderTemplate('sprout-module-data-studio/_components/widgets/Number/body',
                        [
                            'widget' => $this,
                            'result' => $this->getScalarValue($result),
                        ]
                    );
                } catch (Exception $e) {
                    Craft::error($e->getMessage(), __CLASS__);

                    return Craft::t('sprout-module-data-studio', 'Results must be a single number or countable array. Review data set query and try again.');
                }
            }
        }

        return Craft::$app->getView()->renderTemplate('sprout-module-data-studio/_components/widgets/Number/body',
            [
                'widget' => $this,
                'result' => Craft::t('sprout-module-data-studio', 'NaN'),
            ]);
    }

    protected function getScalarValue($result): mixed
    {
        if (!is_array($result)) {
            return $result;
        }

        if (count($result) == 1 && isset($result[0]) && (is_countable($result[0]) ? count($result[0]) : 0) == 1) {
            return array_shift($result[0]);
        }

        return count($result);
    }
}
