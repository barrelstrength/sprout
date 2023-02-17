<?php

namespace BarrelStrength\Sprout\meta\metadata;

use BarrelStrength\Sprout\meta\MetaModule;
use Craft;
use craft\helpers\UrlHelper;

trait MetaImageTrait
{
    public function normalizeImageValue($image = null)
    {
        $element = MetaModule::getInstance()->optimizeMetadata->element;
        $elementMetadataField = MetaModule::getInstance()->optimizeMetadata->elementMetadataField;

        $optimizedImageFieldSetting = $elementMetadataField->optimizedImageField ?? '';

        switch (true) {
            case (is_numeric($image)):
                // Image ID is already available and ready
                $imageId = $image;
                break;

            // Custom Image Field
            case (is_numeric($optimizedImageFieldSetting)):
                $imageId = OptimizeMetadataHelper::getSelectedFieldForOptimizedMetadata($optimizedImageFieldSetting);
                break;

            // Custom Value
            default:
                $imageId = Craft::$app->view->renderObjectTemplate($optimizedImageFieldSetting, $element);
                break;
        }

        return $imageId;
    }

    /**
     * Can be used to prepare the asset metadata for front-end use.
     * Depending on the scenario this method can return just the URL or
     * a list of image attributes. If returning all data, the return value
     * is an array and must be assigned to a list() not a simple $variable.
     */
    public function prepareAssetMetaData($image, $transform = null, bool $urlOnly = true)
    {
        // If it's an URL, use it.
        if (0 === mb_strpos($image, 'http')) {
            return $image;
        }

        if (!is_numeric($image)) {
            Craft::warning('Meta image value [' . $image . '] cannot be identified. Must be an absolute URL or an Asset ID.', __METHOD__);

            return null;
        }

        // If the siteUrl is https or the current request is https, use it.
        $scheme = parse_url(UrlHelper::baseSiteUrl(), PHP_URL_SCHEME);
        $transformSettings = $transform ? MetaModule::getInstance()->optimizeMetadata->getSelectedTransform($transform) : null;

        $asset = Craft::$app->assets->getAssetById($image);

        // If our selected asset was deleted, make sure it is null
        if (!$asset || !$asset->getUrl()) {
            return null;
        }

        $imageUrl = (string)$asset->getUrl();

        if ($transformSettings) {
            $imageUrl = (string)$asset->getUrl($transformSettings);
        }

        // check to see if Asset already has full Site Url in folder Url
        if (UrlHelper::isAbsoluteUrl($imageUrl)) {
            $absoluteUrl = $imageUrl;
        } elseif (UrlHelper::isProtocolRelativeUrl($imageUrl)) {
            $absoluteUrl = $scheme . ':' . $imageUrl;
        } else {
            $absoluteUrl = UrlHelper::siteUrl($imageUrl);
        }

        $imageWidth = null;
        $imageHeight = null;
        $imageType = null;

        if (!$urlOnly) {
            $imageWidth = $asset->width ?? null;
            $imageHeight = $asset->height ?? null;
            $imageType = $asset->mimeType ?? null;

            if ($transformSettings) {
                $imageWidth = $asset->getWidth($transformSettings);
                $imageHeight = $asset->getHeight($transformSettings);
            }
        }

        if (Craft::$app->request->getIsSecureConnection()) {
            $secureUrl = preg_replace('#^http:#i', 'https:', $absoluteUrl);
            $absoluteUrl = $secureUrl;
        }

        if ($urlOnly) {
            return $absoluteUrl;
        }

        return [
            $absoluteUrl,
            $imageWidth,
            $imageHeight,
            $imageType,
        ];
    }
}
