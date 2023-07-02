<?php

namespace BarrelStrength\Sprout\sitemaps\controllers;

use BarrelStrength\Sprout\sitemaps\sitemapmetadata\SitemapMetadataRecord;
use BarrelStrength\Sprout\sitemaps\sitemapmetadata\SitemapsMetadataHelper;
use BarrelStrength\Sprout\sitemaps\sitemaps\SitemapKey;
use BarrelStrength\Sprout\sitemaps\SitemapsModule;
use Craft;
use craft\models\Site;
use craft\web\Controller;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class XmlSitemapController extends Controller
{
    public int|bool|array $allowAnonymous = [
        'render-xml-sitemap',
    ];

    /**
     * Generates an XML sitemapindex or sitemap
     */
    public function actionRenderXmlSitemap($sitemapMetadataUid = null, int $pageNumber = null): Response
    {
        $site = Craft::$app->sites->getCurrentSite();
        $multiSiteSiteIds = [];
        $sitesInGroup = [];

        $xmlSitemapService = SitemapsModule::getInstance()->xmlSitemap;

        if (!SitemapsModule::isEnabled()) {
            throw new NotFoundHttpException('XML Sitemap not enabled.');
        }

        if (Craft::$app->getIsMultiSite()) {
            $sitesInGroup = $xmlSitemapService->getSitemapSites();
            $firstSiteInGroup = $sitesInGroup[0] ?? null;

            // Only render sitemaps for the primary site in a group
            if (!$firstSiteInGroup instanceof Site || $site->id !== $firstSiteInGroup->id) {
                throw new HttpException(404);
            }

            foreach ($sitesInGroup as $siteInGroup) {
                $multiSiteSiteIds[] = (int)$siteInGroup->id;
            }
        }

        $sitemapIndexUrls = [];
        $elements = [];

        $uuidPattern = SitemapsMetadataHelper::UUID_PATTERN;

        if (preg_match("/^$uuidPattern$/", $sitemapMetadataUid)) {
            $sitemapKey = SitemapMetadataRecord::find()
                ->select(['sourceKey'])
                ->where(['enabled' => true])
                ->andWhere(['siteId' => $site->id])
                ->andWhere(['uid' => $sitemapMetadataUid])
                ->scalar();
        } else {
            $sitemapKey = $sitemapMetadataUid;
        }

        switch ($sitemapKey) {
            // Generate Sitemap Index
            case SitemapKey::INDEX:
                $sitemapIndexUrls = $xmlSitemapService->getSitemapIndex($site);
                break;

            // Prepare Singles Sitemap
            case SitemapKey::SINGLES:
                $elements = $xmlSitemapService->getDynamicSitemapElements(
                    $sitemapMetadataUid,
                    $sitemapKey,
                    $pageNumber,
                    $site
                );
                break;

            case SitemapKey::CUSTOM_QUERY:
                $elements = $xmlSitemapService->getDynamicSitemapElements(
                    $sitemapMetadataUid,
                    $sitemapKey,
                    $pageNumber,
                    $site,
                );

                break;

            // Prepare Custom Pages Sitemap
            case SitemapKey::CUSTOM_PAGES:
                if ($multiSiteSiteIds !== []) {
                    $elements = $xmlSitemapService->getCustomPagesUrlsForMultipleIds(
                        $multiSiteSiteIds,
                        $sitesInGroup
                    );
                } else {
                    $elements = $xmlSitemapService->getCustomPagesUrls($site);
                }

                break;

            // Prepare Element Group Sitemap
            default:
                $elements = $xmlSitemapService->getDynamicSitemapElements(
                    $sitemapMetadataUid,
                    $sitemapKey,
                    $pageNumber,
                    $site,
                );
        }

        $headers = Craft::$app->getResponse()->getHeaders();
        $headers->set('Content-Type', 'application/xml');

        // Render a specific sitemap
        if ($sitemapKey) {
            $sitemapTemplate = Craft::getAlias('@Sprout/TemplateRoot/sitemaps/sitemap.twig');

            return $this->renderTemplate($sitemapTemplate, [
                'elements' => $elements,
            ]);
        }

        // Render the sitemapindex if no specific sitemap is defined
        $sitemapIndexTemplate = Craft::getAlias('@Sprout/TemplateRoot/sitemaps/sitemapindex.twig');

        return $this->renderTemplate($sitemapIndexTemplate, [
            'sitemapIndexUrls' => $sitemapIndexUrls,
        ]);
    }
}
