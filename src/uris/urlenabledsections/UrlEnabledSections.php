<?php

namespace BarrelStrength\Sprout\uris\urlenabledsections;

use BarrelStrength\Sprout\uris\components\sectiontypes\CategorySectionType;
use BarrelStrength\Sprout\uris\components\sectiontypes\EntrySectionType;
use BarrelStrength\Sprout\uris\components\sectiontypes\NoSectionSectionType;
use BarrelStrength\Sprout\uris\components\sectiontypes\ProductSectionType;
use Craft;
use craft\events\RegisterComponentTypesEvent;
use yii\base\Component;

class UrlEnabledSections extends Component
{
    public const EVENT_REGISTER_URL_ENABLED_SECTION_TYPES = 'registerUrlEnabledSectionTypes';

    /**
     * @var UrlEnabledSectionType[]
     */
    public array $urlEnabledSectionTypes = [];

    /**
     * Returns all registered Url-Enabled Section Types
     *
     * @return UrlEnabledSectionType[]
     */
    public function getUrlEnabledSectionTypes(): array
    {
        $urlEnabledSectionTypes = [
            EntrySectionType::class,
            CategorySectionType::class,
            NoSectionSectionType::class,
        ];

        if (Craft::$app->getPlugins()->getPlugin('commerce')) {
            $urlEnabledSectionTypes[] = ProductSectionType::class;
        }

        $event = new RegisterComponentTypesEvent([
            'types' => $urlEnabledSectionTypes,
        ]);

        $this->trigger(self::EVENT_REGISTER_URL_ENABLED_SECTION_TYPES, $event);

        return $event->types;
    }
}
