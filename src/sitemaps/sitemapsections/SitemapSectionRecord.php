<?php

namespace BarrelStrength\Sprout\sitemaps\sitemapsections;

use BarrelStrength\Sprout\sitemaps\db\SproutTable;
use Craft;
use craft\db\ActiveRecord;
use craft\db\Query;
use craft\helpers\UrlHelper;
use craft\models\Site;

/**
 * @property int $id
 * @property int $siteId
 * @property string $uniqueKey
 * @property int $urlEnabledSectionId
 * @property int enabled
 * @property string $type
 * @property string $uri
 * @property int $priority
 * @property string $changeFrequency
 */
class SitemapSectionRecord extends ActiveRecord
{
    public const SCENARIO_CUSTOM_SECTION = 'customSection';

    /** Attribute assigned from URL-Enabled Section integration */
    public string $name = '';

    public string $handle = '';

    public static function tableName(): string
    {
        return SproutTable::SITEMAPS;
    }

    public function rules(): array
    {
        $rules = parent::rules();

        $rules [] = [['uri'], 'sectionUri', 'on' => self::SCENARIO_CUSTOM_SECTION];
        $rules [] = [['uri'], 'required', 'on' => self::SCENARIO_CUSTOM_SECTION, 'message' => 'URI cannot be blank.'];

        return $rules;
    }

    public function beforeSave($insert): bool
    {
        if (!$this->uniqueKey) {
            $this->uniqueKey = $this->generateUniqueKey();
        }

        return parent::beforeSave($insert);
    }

    public function generateUniqueKey(): string
    {
        $key = Craft::$app->getSecurity()->generateRandomString(12);

        $result = (new Query())
            ->select('[[uniqueKey]]')
            ->from([SproutTable::SITEMAPS])
            ->where(['[[uniqueKey]]' => $key])
            ->scalar();

        if ($result) {
            // Try again until we have a unique key
            $this->generateUniqueKey();
        }

        return $key;
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

    //    public function behaviors(): array
    //    {
    //        return [
    //            'typecast' => [
    //                'class' => AttributeTypecastBehavior::class,
    //                'attributeTypes' => [
    //                    'id' => AttributeTypecastBehavior::TYPE_INTEGER,
    //                    'siteId' => AttributeTypecastBehavior::TYPE_INTEGER,
    //                    'uniqueKey' => AttributeTypecastBehavior::TYPE_STRING,
    //                    'urlEnabledSectionId' => AttributeTypecastBehavior::TYPE_INTEGER,
    //                    'enabled' => AttributeTypecastBehavior::TYPE_BOOLEAN,
    //                    'type' => AttributeTypecastBehavior::TYPE_STRING,
    //                    'uri' => AttributeTypecastBehavior::TYPE_STRING,
    //                    'priority' => AttributeTypecastBehavior::TYPE_FLOAT,
    //                    'changeFrequency' => AttributeTypecastBehavior::TYPE_STRING,
    //                ],
    //            ],
    //            'typecastAfterFind' => true
    //        ];
    //    }
}
