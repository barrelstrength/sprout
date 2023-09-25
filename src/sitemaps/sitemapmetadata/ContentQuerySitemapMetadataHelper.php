<?php

namespace BarrelStrength\Sprout\sitemaps\sitemapmetadata;

use BarrelStrength\Sprout\sitemaps\db\SproutTable;
use BarrelStrength\Sprout\sitemaps\sitemaps\SitemapKey;
use Craft;
use craft\db\Query;
use craft\elements\Entry;
use craft\helpers\Json;
use craft\models\Site;

class ContentQuerySitemapMetadataHelper
{
    public static function getSitemapUrls(array &$sitemapUrls, array $sites): void
    {
        foreach ($sites as $site) {
            if ($contentQuerySitemapMetadata = self::getCustomQuerySitemapMetadata($site)) {
                foreach ($contentQuerySitemapMetadata as $contentQuery) {

                    $currentConditionRules = Json::decodeIfJson($contentQuery['settings']);
                    $currentCondition = Craft::$app->conditions->createCondition($currentConditionRules);
                    $currentCondition->elementType = Entry::class;

                    $query = $currentCondition->elementType::find();
                    $currentCondition->modifyQuery($query);

                    $totalElements = $query->count();

                    SitemapsMetadataHelper::getPaginatedSitemapUrls($sitemapUrls, $contentQuery, $totalElements);
                }
            }
        }
    }

    // @todo - very similar to getCustomQuerySitemapMetadata method. Normalize getContent + getCustom language.
    public static function getContentQuerySitemapMetadata(Site $site): array
    {
        return SitemapMetadataRecord::find()
            ->where([
                '[[sourceKey]]' => SitemapKey::CONTENT_QUERY,
                '[[siteId]]' => $site->id,
            ])
            ->all();
    }

    public static function getCustomQuerySitemapMetadata(Site $site): array
    {
        // Fetching all Custom Sitemap defined in Sprout SEO
        $contentQuerySitemapMetadata = (new Query())
            ->select('*')
            ->from([SproutTable::SITEMAPS_METADATA])
            ->where(['enabled' => true])
            ->andWhere(['siteId' => $site->id])
            ->andWhere(['sourceKey' => SitemapKey::CONTENT_QUERY])
            ->indexBy('uid')
            ->all();

        $sitemapMetadata = [];

        foreach ($contentQuerySitemapMetadata as $uid => $metadata) {
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
