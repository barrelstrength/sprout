<?php

namespace BarrelStrength\Sprout\datastudio\datasources;

use BarrelStrength\Sprout\datastudio\components\datasources\CommerceOrderHistoryDataSource;
use BarrelStrength\Sprout\datastudio\components\datasources\CommerceProductRevenueDataSource;
use BarrelStrength\Sprout\datastudio\components\datasources\CustomQueryDataSource;
use BarrelStrength\Sprout\datastudio\components\datasources\CustomTwigTemplateQueryDataSource;
use BarrelStrength\Sprout\datastudio\components\datasources\UsersDataSource;
use BarrelStrength\Sprout\datastudio\DataStudioModule;
use Craft;
use craft\events\RegisterComponentTypesEvent;
use yii\base\Component;

class DataSources extends Component
{
    /**
     * Only to be used by Sprout
     */
    public const INTERNAL_SPROUT_EVENT_REGISTER_DATA_SOURCES = 'registerInternalSproutDataSources';

    public const EVENT_REGISTER_DATA_SOURCES = 'registerSproutDataSources';

    /**
     * @var $_dataSources DataSource[]
     */
    private array $_dataSources = [];

    /**
     * Returns all available Data Source classes
     *
     * @return string[]
     */
    public function getDataSourceTypes(): array
    {
        $internalDataSourceTypes = [
            CustomQueryDataSource::class,
            CustomTwigTemplateQueryDataSource::class,
            UsersDataSource::class,
        ];

        $internalEvent = new RegisterComponentTypesEvent([
            'types' => $internalDataSourceTypes,
        ]);

        $this->trigger(self::INTERNAL_SPROUT_EVENT_REGISTER_DATA_SOURCES, $internalEvent);

        if (Craft::$app->getPlugins()->isPluginInstalled('commerce')) {
            $internalDataSourceTypes = array_merge($internalDataSourceTypes, [
                CommerceOrderHistoryDataSource::class,
                CommerceProductRevenueDataSource::class,
            ]);
        }

        $proDataSourceTypes = new RegisterComponentTypesEvent([
            'types' => $internalDataSourceTypes,
        ]);

        if (DataStudioModule::isPro()) {
            $this->trigger(self::EVENT_REGISTER_DATA_SOURCES, $proDataSourceTypes);
        }

        // Get available Data Sets for current edition
        $availableDataSourceTypes = DataStudioModule::isPro()
            ? array_merge($internalEvent->types, $proDataSourceTypes->types)
            : $internalEvent->types;

        // Map data source handles and class names
        $dataSourceTypeHandles = array_map(static function($dataSourceType): string {
            /** @var DataSource $dataSourceType */
            return $dataSourceType::getHandle();
        }, $availableDataSourceTypes);

        return array_combine($dataSourceTypeHandles, $availableDataSourceTypes);
    }

    public function getDataSources(): array
    {
        if (!$this->_dataSources) {
            $this->initDataSources();
        }

        return $this->_dataSources;
    }

    public function getDataSourceTypeByHandle(string $handle): ?string
    {
        $dataSources = $this->getDataSourceTypes();

        return $dataSources[$handle] ?? null;
    }

    public function initDataSources(): void
    {
        $currentUser = Craft::$app->getUser()->getIdentity();
        $dataSourceTypes = $this->getDataSourceTypes();

        $dataSources = [];

        /** @var DataSource $dataSourceType */
        foreach ($dataSourceTypes as $dataSourceType) {
            if (!isset($installedDataSources[$dataSourceType])) {

                $dataSourcePermission = DataStudioModule::p('editDataSet:' . $dataSourceType);

                if (!$currentUser->can($dataSourcePermission) || !class_exists($dataSourceType)) {
                    continue;
                }

                $dataSources[$dataSourceType] = new $dataSourceType();
            }
        }

        uasort($dataSources, static function($a, $b): int {
            /**
             * @var $a DataSource
             * @var $b DataSource
             */
            return $a::displayName() <=> $b::displayName();
        });

        $this->_dataSources = $dataSources;
    }
}
