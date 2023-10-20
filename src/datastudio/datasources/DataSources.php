<?php

namespace BarrelStrength\Sprout\datastudio\datasources;

use BarrelStrength\Sprout\core\components\events\ModifyRelationsTableQueryEvent;
use BarrelStrength\Sprout\core\relations\RelationsTableInterface;
use BarrelStrength\Sprout\core\twig\TemplateHelper;
use BarrelStrength\Sprout\datastudio\components\datasources\CommerceOrderHistoryDataSource;
use BarrelStrength\Sprout\datastudio\components\datasources\CommerceProductRevenueDataSource;
use BarrelStrength\Sprout\datastudio\components\datasources\CustomQueryDataSource;
use BarrelStrength\Sprout\datastudio\components\datasources\CustomTwigTemplateQueryDataSource;
use BarrelStrength\Sprout\datastudio\components\datasources\UsersDataSource;
use BarrelStrength\Sprout\datastudio\components\elements\DataSetElement;
use BarrelStrength\Sprout\datastudio\DataStudioModule;
use Craft;
use craft\events\RegisterComponentTypesEvent;
use craft\helpers\Cp;
use craft\helpers\Template;
use yii\base\Component;

class DataSources extends Component
{
    /**
     * Only to be used by Sprout
     */
    public const INTERNAL_SPROUT_EVENT_REGISTER_DATA_SOURCES = 'registerInternalSproutDataSources';

    public const EVENT_MODIFY_DATA_SOURCE_RELATIONS_QUERY = 'modifyDataSourceRelationsQuery';

    public const EVENT_REGISTER_DATA_SOURCES = 'registerSproutDataSources';

    /**
     * @var $_dataSources DataSource[]
     */
    private ?array $_dataSources = null;

    /**
     * Returns all available Data Source classes
     *
     * @return string[]
     */
    public function getDataSourceTypes(): array
    {
        if ($this->_dataSources) {
            return $this->_dataSources;
        }

        $internalDataSourceTypes = [
            CustomQueryDataSource::class,
            CustomTwigTemplateQueryDataSource::class,
            UsersDataSource::class,
        ];

        if (Craft::$app->getPlugins()->isPluginInstalled('commerce')) {
            $internalDataSourceTypes = array_merge($internalDataSourceTypes, [
                CommerceOrderHistoryDataSource::class,
                CommerceProductRevenueDataSource::class,
            ]);
        }

        $internalEvent = new RegisterComponentTypesEvent([
            'types' => $internalDataSourceTypes,
        ]);

        $this->trigger(self::INTERNAL_SPROUT_EVENT_REGISTER_DATA_SOURCES, $internalEvent);

        $proEvent = new RegisterComponentTypesEvent([
            'types' => $internalDataSourceTypes,
        ]);

        if (DataStudioModule::isPro()) {
            $this->trigger(self::EVENT_REGISTER_DATA_SOURCES, $proEvent);
        }

        // Get available Data Sets for current edition
        $availableDataSourceTypes = DataStudioModule::isPro()
            ? array_merge($internalEvent->types, $proEvent->types)
            : $internalEvent->types;

        $types = array_combine($availableDataSourceTypes, $availableDataSourceTypes);

        $currentUser = Craft::$app->getUser()->getIdentity();

        $types = array_filter($types, static function($type) use ($currentUser) {
            $dataSourcePermission = DataStudioModule::p('viewReports:' . $type);

            return class_exists($type) && $currentUser->can($dataSourcePermission);
        });

        uasort($types, static function($a, $b): int {
            /**
             * @var $a DataSource
             * @var $b DataSource
             */
            return $a::displayName() <=> $b::displayName();
        });

        $this->_dataSources = $types;

        return $this->_dataSources;
    }

    public function getDataSourceRelations(RelationsTableInterface $element): array
    {
        $dataSourceTypes = $element->getAllowedRelationTypes() ?? $this->getDataSourceTypes();

        // @todo - this reference should lean on DataSources module and let form integration extend with andWhere() on query?
        $query = DataSetElement::find()
            ->orderBy('sprout_datasets.name')
            ->where(['in', 'sprout_datasets.type', $dataSourceTypes]);

        $event = new ModifyRelationsTableQueryEvent([
            'element' => $element,
            'query' => $query,
        ]);

        $this->trigger(self::EVENT_MODIFY_DATA_SOURCE_RELATIONS_QUERY, $event);

        $rows = array_map(static function($element) {
            return [
                'name' => $element->name,
                'cpEditUrl' => $element->getCpEditUrl(),
                'type' => $element->getDataSource()::displayName(),
                'actionUrl' => $element->getCpEditUrl(),
            ];
        }, $event->query->all());

        $options = TemplateHelper::optionsFromComponentTypes($dataSourceTypes);

        $optionValues = [
            [
                'label' => Craft::t('sprout-module-data-studio', 'Select Data Set Type...'),
                'value' => '',
            ],
        ];

        foreach ($options as $option) {
            $optionValues[] = $option;
        }

        $createReportSelect = Cp::selectHtml([
            'id' => 'new-data-set',
            'name' => 'new-data-set',
            'options' => $optionValues,
            'value' => '',
        ]);

        $rows[] = [
            'name' => Craft::t('sprout-module-data-studio', 'New Report'),
            'cpEditUrl' => '',
            'type' => Template::raw($createReportSelect),
            'actionUrl' => '',
        ];

        return $rows;
    }
}
