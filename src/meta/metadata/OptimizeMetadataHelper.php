<?php

namespace BarrelStrength\Sprout\meta\metadata;

use BarrelStrength\Sprout\meta\MetaModule;
use Craft;
use craft\base\Field;
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
        $url = null;

        // If not, then process what we have to try to extract the URL
        if (mb_strpos($id, 'http') !== 0) {
            if (!is_numeric($id)) {
                throw new Exception('Meta Image override value "' . $id . '" must be an absolute url.');
            }

            /**
             * @var Asset $asset
             */
            $asset = Craft::$app->elements->getElementById($id);

            if ($asset !== null) {
                $transform = MetaModule::getInstance()->optimizeMetadata->getSelectedTransform($transform);

                $imageUrl = $asset->getUrl($transform);

                // check to see if Asset already has full Site Url in folder Url
                if (str_contains($imageUrl, 'http')) {
                    $url = $asset->getUrl();
                } else {
                    $protocol = Craft::$app->request->getIsSecureConnection() ? 'https' : 'http';
                    $url = UrlHelper::urlWithScheme($imageUrl, $protocol);
                }
            } else {
                // If our selected asset was deleted, make sure it is null
                $url = null;
            }
        }

        return $url;
    }

    public static function getSelectedFieldForOptimizedMetadata($fieldId)
    {
        $value = null;

        $element = MetaModule::getInstance()->optimizeMetadata->element;

        if (is_numeric($fieldId)) {
            /**
             * @var Field $field
             */
            $field = Craft::$app->fields->getFieldById($fieldId);

            // Does the field exist on the element?
            if ($field && isset($element->{$field->handle})) {
                $elementValue = $element->{$field->handle};
                if ($field instanceof Assets) {
                    $value = isset($elementValue[0]) ? $elementValue[0]->id : null;
                } else {
                    $value = $elementValue;
                }
            }
        }

        return $value;
    }
}
