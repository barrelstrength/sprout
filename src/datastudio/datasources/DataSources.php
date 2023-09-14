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
}
