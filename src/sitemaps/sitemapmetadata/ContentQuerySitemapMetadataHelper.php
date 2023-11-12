<?php

namespace BarrelStrength\Sprout\sitemaps\sitemapmetadata;

use BarrelStrength\Sprout\sitemaps\sitemaps\SitemapKey;
use Craft;
use craft\elements\conditions\ElementCondition;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Json;
use craft\models\Site;

class ContentQuerySitemapMetadataHelper
{
    public static function getSitemapUrls(array &$sitemapUrls, array $sites): void
    {
        foreach ($sites as $site) {
            if ($contentQuerySitemapMetadata = self::getContentQueryXmlSitemapMetadata($site)) {
                foreach ($contentQuerySitemapMetadata as $contentQuery) {
                    $currentConditionRules = Json::decodeIfJson($contentQuery['settings']);

                    /** @var ElementCondition $currentCondition */
                    $currentCondition = Craft::$app->conditions->createCondition($currentConditionRules);
                    $currentCondition->elementType = $contentQuery['type'];

                    $query = $currentCondition->elementType::find();

                    $currentCondition->modifyQuery($query);

                    $totalElements = $query->count();

                    SitemapsMetadataHelper::getPaginatedSitemapUrls($sitemapUrls, $contentQuery, $totalElements);
                }
            }
        }
    }

    public static function getContentQueryXmlSitemapMetadata(Site $site): array
    {
        $contentQuerySitemapMetadata = SitemapMetadataRecord::find()
            ->where([
                '[[sourceKey]]' => SitemapKey::CONTENT_QUERY,
                '[[siteId]]' => $site->id,
                '[[enabled]]' => true,
            ])
            ->indexBy('uid')
            ->all();

        $sitemapMetadata = [];

        foreach ($contentQuerySitemapMetadata as $uid => $metadata) {
            $sitemapMetadata[$uid] = new SitemapMetadataRecord($metadata);
        }

        return $sitemapMetadata;
    }

    public static function getContentQuerySitemapMetadata(Site $site): array
    {
        return SitemapMetadataRecord::find()
            ->where([
                '[[sourceKey]]' => SitemapKey::CONTENT_QUERY,
                '[[siteId]]' => $site->id,
            ])
            ->all();
    }

    public static function getElementQuery(SitemapMetadataRecord $sitemapMetadata): ElementQueryInterface
    {
        $conditionRules = Json::decodeIfJson($sitemapMetadata['settings']);

        /** @var ElementCondition $condition */
        $condition = Craft::$app->conditions->createCondition($conditionRules);
        $condition->elementType = $sitemapMetadata->type;

        $elementQuery = $condition->elementType::find();
        $condition->modifyQuery($elementQuery);

        return $elementQuery;
    }
}
