<?php

namespace BarrelStrength\Sprout\uris\components\elements;

use BarrelStrength\Sprout\uris\elementgroups\ElementGroup;
use BarrelStrength\Sprout\uris\elementgroups\ElementGroupInterface;
use Craft;
use craft\elements\Category;
use craft\models\CategoryGroup;
use yii\base\Behavior;

/**
 * Extends Elements with Sitemap-specific behaviors
 *
 * Access directly from the Element:
 * - element.attribute
 * - element.method()
 *
 * @see UrisModule::attachElementBehaviors() for initialization
 *
 * @property Category $owner
 */
class CategoryElementGroupBehavior extends Behavior implements ElementGroupInterface
{
    public function setElementGroupId(CategoryGroup $elementGroup): void
    {
        $this->owner->groupId = $elementGroup->id;
    }

    /**
     * @return ElementGroup[]
     */
    public function defineElementGroups($siteId): array
    {
        $elementGroups = [];

        $sections = Craft::$app->categories->getAllGroups();

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
