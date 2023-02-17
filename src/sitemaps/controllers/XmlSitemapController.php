<?php

namespace BarrelStrength\Sprout\sitemaps\controllers;

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
    public function actionRenderXmlSitemap($sitemapKey = null, int $pageNumber = null): Response
    {
        $siteId = Craft::$app->sites->getCurrentSite()->id;
        $multiSiteSiteIds = [];
        $sitesInGroup = [];

        if (!SitemapsModule::isEnabled()) {
            throw new NotFoundHttpException('XML Sitemap not enabled.');
        }

        if (Craft::$app->getIsMultiSite()) {
            $sitesInGroup = SitemapsModule::getInstance()->xmlSitemap->getCurrentSitemapSites();
            $firstSiteInGroup = $sitesInGroup[0] ?? null;

            // Only render sitemaps for the primary site in a group
            if (!$firstSiteInGroup instanceof Site || $siteId !== $firstSiteInGroup->id) {
                throw new HttpException(404);
            }

            foreach ($sitesInGroup as $siteInGroup) {
                $multiSiteSiteIds[] = (int)$siteInGroup->id;
            }
        }

        $sitemapIndexUrls = [];
        $elements = [];

        switch ($sitemapKey) {
            // Generate Sitemap Index
            case '':
                $sitemapIndexUrls = SitemapsModule::getInstance()->xmlSitemap->getSitemapIndex($siteId);
                break;

            // Prepare Singles Sitemap
            case 'singles':
                $elements = SitemapsModule::getInstance()->xmlSitemap->getDynamicSitemapElements('singles', $pageNumber, $siteId);
                break;

            // Prepare Custom Pages Sitemap
            case 'custom-pages':
                if ($multiSiteSiteIds !== []) {
                    $elements = SitemapsModule::getInstance()->xmlSitemap->getCustomSectionUrlsForMultipleIds($multiSiteSiteIds, $sitesInGroup);
                } else {
                    $elements = SitemapsModule::getInstance()->xmlSitemap->getCustomSectionUrls($siteId);
                }

                break;

            // Prepare URL-Enabled Section Sitemap
            default:
                $elements = SitemapsModule::getInstance()->xmlSitemap->getDynamicSitemapElements($sitemapKey, $pageNumber, $siteId);
        }

        $headers = Craft::$app->getResponse()->getHeaders();
        $headers->set('Content-Type', 'application/xml');

        // Render a specific sitemap
        if ($sitemapKey) {
            return $this->renderTemplate(Craft::getAlias('@Sprout/TemplateRoot/sitemaps/sitemap'), [
                'elements' => $elements,
            ]);
        }

        // Render the sitemapindex if no specific sitemap is defined
        return $this->renderTemplate(Craft::getAlias('@Sprout/TemplateRoot/sitemaps/sitemapindex'), [
            'sitemapIndexUrls' => $sitemapIndexUrls,
        ]);
    }
}
