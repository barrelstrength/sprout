<?php

namespace BarrelStrength\Sprout\sitemaps\controllers;

use BarrelStrength\Sprout\core\helpers\RegexHelper;
use BarrelStrength\Sprout\sitemaps\sitemapmetadata\CustomPagesSitemapMetadataHelper;
use BarrelStrength\Sprout\sitemaps\sitemapmetadata\SitemapMetadataRecord;
use BarrelStrength\Sprout\sitemaps\sitemapmetadata\SitemapsMetadataHelper;
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
        $sites = SitemapsMetadataHelper::getSitemapSites($site);
        $siteIds = array_keys($sites);

        SitemapsMetadataHelper::isValidSitemapRequest($siteIds, $site);

        // Two scenarios:
        // Single Site - $sites should be an array of one site
        // Multi-Site - $sites should be an array of one or more sites

        switch ($sitemapKey) {
            // Generate Sitemap Index
            case SitemapKey::INDEX:
                $sitemapIndexUrls = $xmlSitemapService->getSitemapIndex($sites);
                break;

            // Prepare Custom Pages Sitemap
            case SitemapKey::CUSTOM_PAGES:
                $elements = CustomPagesSitemapMetadataHelper::getCustomPagesUrls($sites);
                break;

            case SitemapKey::SINGLES:
            case SitemapKey::CUSTOM_QUERY:
            default:
                // Single Site - uses the current site, which is the only site in $sites array
                // Multi-Site - uses the Primary Site in the group, which is the first site in $sites array
                $sitemapSite = reset($sites);

                $elements = $xmlSitemapService->getDynamicSitemapElements(
                    $sitemapMetadataUid,
                    $sitemapKey,
                    $pageNumber,
                    $sites,
                    $sitemapSite,
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
