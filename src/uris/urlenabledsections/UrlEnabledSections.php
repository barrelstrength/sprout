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
    public function getRegisteredUrlEnabledSectionsEvent(): array
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

    public function getUrlEnabledSectionTypes(): array
    {
        $urlEnabledSectionTypes = $this->getRegisteredUrlEnabledSectionsEvent();

        $urlEnabledSections = [];

        foreach ($urlEnabledSectionTypes as $urlEnabledSectionType) {
            $urlEnabledSections[] = new $urlEnabledSectionType();
        }

        uasort($urlEnabledSections, static function($a, $b): int {
            /**
             * @var $a UrlEnabledSectionType
             * @var $b UrlEnabledSectionType
             */
            return $a->getName() <=> $b->getName();
        });

        return $urlEnabledSections;
    }

    public function getMatchedElementVariables(): array
    {
        $urlEnabledSections = $this->getUrlEnabledSectionTypes();

        $matchedElementVariables = [];

        foreach ($urlEnabledSections as $urlEnabledSection) {
            $matchedElementVariables[] = $urlEnabledSection->getMatchedElementVariable();
        }

        return array_filter($matchedElementVariables);
    }

    /**
     * Get the active URL-Enabled Section Type via the Element Type
     */
    public function getUrlEnabledSectionTypeByElementType($elementType): ?UrlEnabledSectionType
    {
        $currentSite = Craft::$app->sites->getCurrentSite();
        $this->prepareUrlEnabledSectionTypesForMetadataField($currentSite->id);

        foreach ($this->urlEnabledSectionTypes as $urlEnabledSectionType) {

            if ($urlEnabledSectionType->getElementType() == $elementType) {
                return $urlEnabledSectionType;
            }
        }

        return null;
    }

    public function prepareUrlEnabledSectionTypesForMetadataField($siteId): void
    {
        $registeredUrlEnabledSectionsTypes = $this->getRegisteredUrlEnabledSectionsEvent();

        foreach ($registeredUrlEnabledSectionsTypes as $urlEnabledSectionType) {

            /** @var UrlEnabledSectionType $urlEnabledSectionType */
            $urlEnabledSectionType = new $urlEnabledSectionType();
            $allUrlEnabledSections = $urlEnabledSectionType->getAllUrlEnabledSections($siteId);
            $urlEnabledSections = [];

            /** @var UrlEnabledSection $urlEnabledSection */
            foreach ($allUrlEnabledSections as $urlEnabledSection) {
                $uniqueKey = $urlEnabledSectionType->getId() . '-' . $urlEnabledSection->id;
                $model = new UrlEnabledSection();

                $model->type = $urlEnabledSectionType;
                $model->id = $urlEnabledSection->id;
                $urlEnabledSections[$uniqueKey] = $model;
            }

            $urlEnabledSectionType->urlEnabledSections = $urlEnabledSections;

            $this->urlEnabledSectionTypes[$urlEnabledSectionType->getId()] = $urlEnabledSectionType;
        }
    }
}
