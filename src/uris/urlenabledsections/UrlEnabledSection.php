<?php

namespace BarrelStrength\Sprout\uris\urlenabledsections;

use BarrelStrength\Sprout\meta\components\fields\ElementMetadataField;
use BarrelStrength\Sprout\sitemaps\sitemapsections\SitemapSectionRecord;
use Craft;
use craft\base\Element;
use craft\base\Model;
use craft\db\Query;
use craft\models\FieldLayout;

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

    public function hasElementMetadataField(bool $matchAll = true): bool
    {
        $fieldLayoutObjects = $this->getFieldLayoutObjects();

        if ($fieldLayoutObjects === false) {
            return false;
        }

        $totalFieldLayouts = is_countable($fieldLayoutObjects) ? count($fieldLayoutObjects) : 0;
        $totalElementMetaFields = 0;

        // We want to make sure there is an Element Metadata field on every field layout object.
        // For example, a Category Group or Product Type just needs one Element Metadata for its Field Layout.
        // A section with multiple Entry Types needs an Element Metadata field on each of it's Field Layouts.
        foreach ($fieldLayoutObjects as $fieldLayoutObject) {
            /** @var FieldLayout $fieldLayout */
            $fieldLayout = $fieldLayoutObject->getFieldLayout();
            $fields = $fieldLayout->getCustomFieldElements();

            foreach ($fields as $field) {
                $customField = $field->getField();
                if ($customField::class === ElementMetadataField::class) {
                    $totalElementMetaFields++;
                }
            }
        }

        if ($matchAll) {
            // If we have an equal number of Element Metadata fields,
            // the setup is optimized to handle metadata at each level
            // We use this to indicate to the user if everything is setup
            if ($totalElementMetaFields >= $totalFieldLayouts) {
                return true;
            }
        } elseif ($totalElementMetaFields > 0) {
            // When we're resaving our elements, we don't care if everything is
            // setup, we just need to know if any Element Metadata Fields exist
            // and need updating.
            return true;
        }

        return false;
    }

    public function hasFieldLayoutId($fieldLayoutId): bool
    {
        $fieldLayoutObjects = $this->getFieldLayoutObjects();

        if ($fieldLayoutObjects === false) {
            return false;
        }

        foreach ($fieldLayoutObjects as $fieldLayoutObject) {
            $fieldLayout = $fieldLayoutObject->getFieldLayout();

            if ($fieldLayout->id == $fieldLayoutId) {
                return true;
            }
        }

        return false;
    }

    private function getFieldLayoutObjects(): array|bool
    {
        $fieldLayoutObjects = $this->type->getFieldLayoutSettingsObject($this->id);

        if (!$fieldLayoutObjects) {
            return false;
        }

        // Make what we get back into an array
        if (!is_array($fieldLayoutObjects)) {
            $fieldLayoutObjects = [$fieldLayoutObjects];
        }

        return $fieldLayoutObjects;
    }
}
