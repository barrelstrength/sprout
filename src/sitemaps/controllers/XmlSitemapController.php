<?php

namespace BarrelStrength\Sprout\sitemaps\controllers;

use BarrelStrength\Sprout\core\helpers\RegexHelper;
use BarrelStrength\Sprout\sitemaps\sitemapmetadata\CustomPagesSitemapMetadataHelper;
use BarrelStrength\Sprout\sitemaps\sitemapmetadata\SitemapMetadataRecord;
use BarrelStrength\Sprout\sitemaps\sitemaps\SitemapKey;
use BarrelStrength\Sprout\sitemaps\SitemapsModule;
use Craft;
use craft\models\Site;
use craft\web\Controller;
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
    public function actionRenderXmlSitemap(string $sitemapMetadataUid = null, int $pageNumber = null): Response
    {
        $site = Craft::$app->sites->getCurrentSite();

        $xmlSitemapService = SitemapsModule::getInstance()->xmlSitemap;

        if (!SitemapsModule::isEnabled()) {
            throw new NotFoundHttpException('XML Sitemaps not enabled.');
        }

        $elements = [];
        $sitemapIndexUrls = [];
        $sitemapKey = $this->getSitemapKey($sitemapMetadataUid, $site);
        [$sitesInGroup, $multiSiteSiteIds] = SitemapsMetadataHelper::getSiteInfo($site);
        $sites = SitemapsMetadataHelper::getSitemapSites();

        if (empty($sites)) {
            throw new NotFoundHttpException('XML Sitemap not enabled for this site.');
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
                if (!empty($multiSiteSiteIds)) {
                    $elements = CustomPagesSitemapMetadataHelper::getCustomPagesUrlsForMultipleIds(
                        $multiSiteSiteIds,
                        $sitesInGroup
                    );
                } else {
                    $elements = CustomPagesSitemapMetadataHelper::getCustomPagesUrls($site);
                }

                break;

            // Prepare Content Sitemap
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

    protected function getSitemapKey(mixed $sitemapMetadataUid, Site $site): mixed
    {
        $uuidPattern = RegexHelper::UUID_PATTERN;

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

        return $sitemapKey;
    }
}
