<?php

namespace BarrelStrength\Sprout\uris\urlenabledsections;

use BarrelStrength\Sprout\sitemaps\sitemapsections\SitemapSectionRecord;
use Craft;
use craft\base\Element;
use craft\base\Model;
use craft\db\Query;

class UrlEnabledSection extends Model
{
    public int $id;

    public UrlEnabledSectionType $type;

    public SitemapSectionRecord $sitemapSection;

    /**
     * The current locales URL Format for this URL-Enabled Section
     */
    public string $uriFormat;

    /**
     * The Element Model for the Matched Element Variable of the current page load
     */
    public Element $element;

    /**
     * Name of the Url Enabled Element Group
     */
    public string $name;

    /**
     * Handle of the Url Enabled Element Group
     */
    public string $handle;

    /**
     * Get the URL format from the element via the Element Group integration
     */
    public function getUrlFormat(): string
    {
        $primarySite = Craft::$app->getSites()->getPrimarySite();

        $urlEnabledSectionUrlFormatTableName = $this->type->getTableName();
        $urlEnabledSectionUrlFormatColumnName = $this->type->getUrlFormatColumnName();
        $urlEnabledSectionIdColumnName = $this->type->getUrlFormatIdColumnName();

        $query = (new Query())
            ->select($urlEnabledSectionUrlFormatColumnName)
            ->from(["{{%$urlEnabledSectionUrlFormatTableName}}"])
            ->where([$urlEnabledSectionIdColumnName => $this->id]);

        if ($this->type->isLocalized()) {
            $query->andWhere(['siteId' => $primarySite->id]);
        }

        if ($query->scalar()) {
            $this->uriFormat = $query->scalar();
        }

        return $this->uriFormat;
    }
}
