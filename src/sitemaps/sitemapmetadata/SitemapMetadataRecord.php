<?php

namespace BarrelStrength\Sprout\sitemaps\sitemapmetadata;

use BarrelStrength\Sprout\sitemaps\db\SproutTable;
use BarrelStrength\Sprout\sitemaps\SitemapsModule;
use Craft;
use craft\base\Element;
use craft\db\ActiveRecord;
use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\helpers\UrlHelper;
use craft\models\Site;

/**
 * @property int $id
 * @property int $siteId
 * @property string $sourceKey
 * @property string $sitemapKey
 * @property int enabled
 * @property string $type
 * @property string $uri
 * @property int $priority
 * @property string $changeFrequency
 * @property string $description
 * @property string $settings
 */
class SitemapMetadataRecord extends ActiveRecord
{
    public const SCENARIO_CUSTOM_QUERY = 'customQuery';
    public const SCENARIO_CUSTOM_SECTION = 'customSection';

    /** Attribute assigned from URL-Enabled Section integration */
    public string $name = '';

    public string $handle = '';

    public function getElementQuery(): ElementQuery
    {
        /** @var Element|string $elementType */
        $elementType = $this->type;

        $query = $elementType::find()
            ->siteId($this->siteId);

        $sitemapMetadataTypes = SitemapsModule::getInstance()->sitemaps->getSitemapMetadataTypes();

        if (isset($sitemapMetadataTypes[$elementType])) {
            $integration = new $sitemapMetadataTypes[$elementType]();

            return $integration->getElementQuery($query, $this);
        }

        // Defaults to just returning ALL elements on an unknown Element Type
        return $query;
    }

    public static function tableName(): string
    {
        return SproutTable::SITEMAPS_METADATA;
    }

    public function rules(): array
    {
        $rules = parent::rules();

        $rules [] = [['uri'], 'sectionUri', 'on' => self::SCENARIO_CUSTOM_SECTION];
        $rules [] = [['uri'], 'required', 'on' => self::SCENARIO_CUSTOM_SECTION, 'message' => 'URI cannot be blank.'];
        $rules [] = [['description'], 'required', 'on' => self::SCENARIO_CUSTOM_QUERY, 'message' => 'Description is required.'];
        $rules [] = [['settings'], 'required', 'on' => self::SCENARIO_CUSTOM_QUERY, 'message' => 'Must define at least one query condition.'];

        return $rules;
    }

    public function beforeSave($insert): bool
    {
        if (!$this->sitemapKey) {
            $this->sitemapKey = $this->generateUniqueKey();
        }

        return parent::beforeSave($insert);
    }

    public function generateUniqueKey(): string
    {
        $sitemapKey = Craft::$app->getSecurity()->generateRandomString(12);

        $result = (new Query())
            ->select('[[sitemapKey]]')
            ->from([SproutTable::SITEMAPS_METADATA])
            ->where(['[[sitemapKey]]' => $sitemapKey])
            ->scalar();

        if ($result) {
            // Try again until we have a unique key
            $sitemapKey = $this->generateUniqueKey();
        }

        return $sitemapKey;
    }

    public function getSite(): ?Site
    {
        return Craft::$app->sites->getSiteById($this->siteId);
    }

    /**
     * Check is the url saved on custom sections are URI's
     * This is the 'sectionUri' validator as declared in rules().
     */
    public function sectionUri($attribute): void
    {
        if (UrlHelper::isAbsoluteUrl($this->$attribute)) {
            $this->addError($attribute, Craft::t('sprout-module-sitemaps', 'Invalid URI. The URI should only include valid segments of your URL that come after the base domain. i.e. {siteUrl}URI', [
                'siteUrl' => UrlHelper::siteUrl(),
            ]));
        }
    }
}
