<?php

namespace BarrelStrength\Sprout\uris\components\elements;

use BarrelStrength\Sprout\uris\elementgroups\ElementGroup;
use BarrelStrength\Sprout\uris\elementgroups\ElementGroupInterface;
use Craft;
use craft\elements\Entry;
use craft\models\Section;
use yii\base\Behavior;

/**
 * Extends Elements with Sitemap Metadata behaviors
 *
 * Access directly from the Element:
 * - element.attribute
 * - element.method()
 *
 * @see SitemapModule::attachElementBehaviors() for initialization
 *
 * @property Entry $owner
 */
class EntryElementGroupBehavior extends Behavior implements ElementGroupInterface
{
    public function setElementGroupId(Section $elementGroup): void
    {
        $this->owner->sectionId = $elementGroup->id;
    }

    /**
     * @return ElementGroup[]
     */
    public function defineElementGroups($siteId): array
    {
        $elementGroups = [];

        $sections = Craft::$app->sections->getAllSections();

        foreach ($sections as $section) {
            $siteSettings = $section->getSiteSettings();

            foreach ($siteSettings as $siteSetting) {
                if ($siteId == $siteSetting->siteId && $siteSetting->hasUrls) {
                    $elementGroups[] = $section;
                }
            }
        }

        return $elementGroups;
    }
}
