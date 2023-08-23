<?php

namespace BarrelStrength\Sprout\uris\links;

use BarrelStrength\Sprout\core\helpers\ComponentHelper;
use BarrelStrength\Sprout\core\twig\TemplateHelper;
use BarrelStrength\Sprout\uris\components\links\AbsoluteUrl;
use BarrelStrength\Sprout\uris\components\links\CategoryElementLink;
use BarrelStrength\Sprout\uris\components\links\CurrentPageUrl;
use BarrelStrength\Sprout\uris\components\links\EmailLink;
use BarrelStrength\Sprout\uris\components\links\EntryElementLink;
use BarrelStrength\Sprout\uris\components\links\RelativeUrl;
use BarrelStrength\Sprout\uris\UrisModule;
use Craft;
use craft\base\Component;
use craft\events\RegisterComponentTypesEvent;
use craft\helpers\Cp;
use craft\helpers\Json;

class Links extends Component
{
    public const EVENT_REGISTER_LINK_TYPES = 'registerSproutLinkTypes';

    public function getLinkTypes(array $excludedLinks = []): array
    {
        $linkTypes = [
            CurrentPageUrl::class,
            AbsoluteUrl::class,
            RelativeUrl::class,
            EmailLink::class,
            //PhoneLink::class,
            EntryElementLink::class,
            CategoryElementLink::class,
        ];

        $event = new RegisterComponentTypesEvent([
            'types' => $linkTypes,
        ]);

        $this->trigger(self::EVENT_REGISTER_LINK_TYPES, $event);

        // filter out excluded links
        return array_filter($event->types, static function($linkType) use ($excludedLinks) {
            return !in_array($linkType, $excludedLinks, true);
        });
    }

    public static function enhancedLinkFieldHtml(array $config = [], array $excludedLinks = []): string
    {
        $linksService = UrisModule::getInstance()->links;

        $linkTypes = $linksService->getLinkTypes($excludedLinks);
        $variables['links'] = ComponentHelper::typesToInstances($linkTypes);
        $variables['linkOptions'] = TemplateHelper::optionsFromComponentTypes($linkTypes);

        return Cp::renderTemplate('sprout-module-uris/links/input.twig', array_merge($config, $variables));
    }

    public static function toLinkField(mixed $value): LinkInterface|false
    {
        if ($value instanceof LinkInterface) {
            return $value;
        }

        if (!$value = Json::decodeIfJson($value)) {
            return false;
        }

        $value['class'] = $value['type'];
        unset($value['type']);

        $linkType = Craft::createObject($value);

        return $linkType;
    }
}
