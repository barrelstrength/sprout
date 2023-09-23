<?php

namespace BarrelStrength\Sprout\sitemaps\sitemapmetadata;

use BarrelStrength\Sprout\sitemaps\db\SproutTable;
use BarrelStrength\Sprout\sitemaps\sitemaps\SitemapKey;
use Craft;
use craft\db\Query;
use craft\elements\Entry;
use craft\helpers\Json;
use craft\models\Site;

class CustomQuerySitemapMetadataHelper
{
    public static function getSitemapUrls(array &$sitemapUrls, Site $site): void
    {
        if ($customQuerySitemapMetadata = self::getCustomQuerySitemapMetadata($site)) {
            foreach ($customQuerySitemapMetadata as $customQuery) {

                $currentConditionRules = Json::decodeIfJson($customQuery['settings']);
                $currentCondition = Craft::$app->conditions->createCondition($currentConditionRules);
                $currentCondition->elementType = Entry::class;

                $query = $currentCondition->elementType::find();
                $currentCondition->modifyQuery($query);

                $totalElements = $query->count();

                $sitemapUrls = SitemapsMetadataHelper::getPaginatedSitemapUrls($sitemapUrls, $customQuery, $totalElements);
            }
        }
    }

    // @todo - very similar to getCustomQuerySitemapMetadata method. Normalize getContent + getCustom language.
    public static function getContentQuerySitemapMetadata(Site $site): array
    {
        return SitemapMetadataRecord::find()
            ->where([
                '[[sourceKey]]' => SitemapKey::CUSTOM_QUERY,
                '[[siteId]]' => $site->id,
            ])
            ->all();
    }

    public static function getCustomQuerySitemapMetadata(Site $site): array
    {
        // Fetching all Custom Sitemap defined in Sprout SEO
        $customQuerySitemapMetadata = (new Query())
            ->select('*')
            ->from([SproutTable::SITEMAPS_METADATA])
            ->where(['enabled' => true])
            ->andWhere(['siteId' => $site->id])
            ->andWhere(['sourceKey' => SitemapKey::CUSTOM_QUERY])
            ->indexBy('uid')
            ->all();

        $sitemapMetadata = [];

        foreach ($customQuerySitemapMetadata as $uid => $metadata) {
            $sitemapMetadata[$uid] = new SitemapMetadataRecord($metadata);
        }

        return $sitemapMetadata;
    }

    public static function getElementQuery(SitemapMetadataRecord $sitemapMetadata)
    {
        $conditionRules = Json::decodeIfJson($sitemapMetadata['settings']);
        $condition = Craft::$app->conditions->createCondition($conditionRules);
        $condition->elementType = $sitemapMetadata->type;

        $elementQuery = $condition->elementType::find();
        $condition->modifyQuery($elementQuery);

        return $elementQuery;
    }
}
