<?php

namespace BarrelStrength\Sprout\meta\metadata;

use BarrelStrength\Sprout\meta\MetaModule;
use Craft;
use craft\elements\Asset;
use craft\fields\Assets;
use craft\helpers\UrlHelper;
use yii\base\Exception;

class OptimizeMetadataHelper
{
    public static function handleRenderMetadata(): void
    {
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            return;
        }

        if (!Craft::$app->getRequest()->getIsSiteRequest()) {
            return;
        }

        $site = Craft::$app->getSites()->getCurrentSite();

        MetaModule::getInstance()->optimizeMetadata->registerMetadata($site);
    }

    public static function getCanonical($value = null): ?string
    {
        if ($value) {
            return $value;
        }

        return UrlHelper::siteUrl(Craft::$app->request->getPathInfo());
    }

    public static function getAssetUrl($id, $transform = null): ?string
    {
        // If not, then process what we have to try to extract the URL
        if (mb_strpos($id, 'http') === 0) {
            return null;
        }

        if (!is_numeric($id)) {
            throw new Exception('Meta Image override value "' . $id . '" must be an absolute url.');
        }

        $asset = Craft::$app->elements->getElementById($id);

        if (!$asset instanceof Asset) {
            return null;
        }

        $transform = MetaModule::getInstance()->optimizeMetadata->getSelectedTransform($transform);

         if (!$imageUrl = $asset->getUrl($transform)) {
             return null;
         }

        // check to see if Asset already has full Site Url in folder Url
        if (str_contains($imageUrl, 'http')) {
            return $asset->getUrl();
        }

        $protocol = Craft::$app->request->getIsSecureConnection() ? 'https' : 'http';

        return UrlHelper::urlWithScheme($imageUrl, $protocol);
    }

    public static function getSelectedFieldForOptimizedMetadata(int $fieldId)
    {
        $field = Craft::$app->fields->getFieldById($fieldId);

        if (!$field) {
            return null;
        }

        $element = MetaModule::getInstance()->optimizeMetadata->element;
        $value = $element->{$field->handle} ?? null;

        if (!$value) {
            return null;
        }

        if ($field instanceof Assets) {
            return isset($value[0]) ? $value[0]->id : null;
        }

        return $value;
    }
}
