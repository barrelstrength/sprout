<?php

namespace BarrelStrength\Sprout\uris\links;

use BarrelStrength\Sprout\uris\components\links\CategoryElementLink;
use BarrelStrength\Sprout\uris\components\links\EmailLink;
use BarrelStrength\Sprout\uris\components\links\EntryElementLink;
use BarrelStrength\Sprout\uris\components\links\AbsoluteUrl;
use BarrelStrength\Sprout\uris\components\links\PhoneLink;
use BarrelStrength\Sprout\uris\components\links\RelativeUrl;
use craft\base\Component;
use craft\events\RegisterComponentTypesEvent;
use craft\helpers\Cp;
use craft\helpers\Json;
use Craft;

class Links extends Component
{
    public const EVENT_REGISTER_LINK_TYPES = 'registerSproutLinkTypes';

    public function getLinkTypes(array $excludedLinks = []): array
    {
        $linkTypes = [
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

    public function getLinkInstances(array $excludedLinks = []): array
    {
        $linkTypes = $this->getLinkTypes($excludedLinks);

        return array_map(static function($linkType) {
            return new $linkType();
        }, $linkTypes);
    }

    public function getLinkOptions(array $excludedLinks = []): array
    {
        $linkTypes = $this->getLinkTypes($excludedLinks);

        return array_map(static function($type) {
            return [
                'label' => $type::displayName(),
                'value' => $type,
            ];
        }, $linkTypes);
    }

    public static function enhancedLinkFieldHtml(array $config = []): string
    {
        return Cp::renderTemplate('sprout-module-uris/links/input.twig', $config);
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