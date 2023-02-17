<?php

namespace BarrelStrength\Sprout\forms\formfields;

use Craft;
use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\Tag;
use craft\elements\User;
use craft\models\Section;
use yii\base\Component;

class FrontEndFields extends Component
{
    public function getFrontEndEntries(array $settings): array
    {
        $entries = [];
        $sectionsService = Craft::$app->getSections();

        if (is_array($settings['sources'])) {
            foreach ($settings['sources'] as $source) {
                $section = explode(':', $source);
                $pos = count($entries) + 1;

                if (count($section) == 2) {
                    $sectionModel = $sectionsService->getSectionByUid($section[1]);
                    if ($sectionModel === null) {
                        Craft::warning("Unable to find Section: $section[1]", __METHOD__);

                        return [];
                    }

                    $entryQuery = Entry::find()->sectionId($sectionModel->id);
                    if ($sectionModel->type == Section::TYPE_CHANNEL) {
                        $entryQuery->orderBy(['title' => SORT_ASC]);
                    }

                    $entries[$pos]['entries'] = $entryQuery->all();
                    $entries[$pos]['section'] = $sectionModel;
                } elseif ($section[0] == 'singles') {
                    $singles = $this->getSinglesEntries();
                    $entries[$pos]['entries'] = $singles;
                    $entries[$pos]['singles'] = true;
                }
            }
        } elseif ($settings['sources'] == '*') {
            $sections = $sectionsService->getAllSections();
            foreach ($sections as $section) {
                $pos = count($entries) + 1;
                if ($section->type != Section::TYPE_SINGLE) {
                    $sectionModel = $sectionsService->getSectionById($section->id);

                    $entryQuery = Entry::find()->sectionId($section->id);

                    if ($section->type == Section::TYPE_CHANNEL) {
                        $entryQuery->orderBy(['title' => SORT_ASC]);
                    }

                    $entries[$pos]['entries'] = $entryQuery->all();
                    $entries[$pos]['section'] = $sectionModel;
                }
            }

            $singles = $this->getSinglesEntries();
            $pos = count($entries) + 1;
            $entries[$pos]['entries'] = $singles;
            $entries[$pos]['singles'] = true;
        }

        return $entries;
    }

    public function getFrontEndUsers($settings): array
    {
        $users = [];
        $userGroups = Craft::$app->getUserGroups();

        if (is_array($settings['sources'])) {
            foreach ($settings['sources'] as $source) {
                $section = explode(':', $source);
                $pos = count($users) + 1;

                if (count($section) == 2) {
                    $groupModel = $userGroups->getGroupByUid($section[1]);
                    if ($groupModel === null) {
                        Craft::warning("Unable to find User Group: $section[1]", __METHOD__);

                        return [];
                    }

                    $entryQuery = User::find()->groupId($groupModel->id);
                    $usersResult = $entryQuery->all();
                    if ($usersResult) {
                        $users[$pos]['users'] = $usersResult;
                        $users[$pos]['group'] = $groupModel;
                    }
                } elseif ($section[0] == 'admins') {
                    $entryQuery = User::find()->admin();
                    $users[$pos]['users'] = $entryQuery->all();
                    $users[$pos]['group'] = 'admin';
                }
            }
        } elseif ($settings['sources'] == '*') {
            $groups = $userGroups->getAllGroups();
            $pos = count($users) + 1;
            $entryQuery = User::find()->admin();
            $users[$pos]['users'] = $entryQuery->all();
            $users[$pos]['group'] = 'admin';
            foreach ($groups as $group) {
                $pos = count($users) + 1;
                $groupModel = $userGroups->getGroupById($group->id);

                $entryQuery = User::find()->groupId($group->id);
                $usersResult = $entryQuery->all();
                if ($usersResult) {
                    $users[$pos]['users'] = $usersResult;
                    $users[$pos]['group'] = $groupModel;
                }
            }
        }

        return $users;
    }

    public function getFrontEndCategories(array $settings): array
    {
        $categories = [];

        if (isset($settings['source'])) {
            $group = explode(':', $settings['source']);
            $pos = count($categories) + 1;

            if (count($group) == 2) {
                $categoryGroup = Craft::$app->getCategories()->getGroupByUid($group[1]);

                if ($categoryGroup === null) {
                    Craft::warning("Unable to find Category Group: $group[1]", __METHOD__);

                    return [];
                }

                $categories[$pos]['categories'] = Category::find()->groupId($categoryGroup->id)->all();
                $categories[$pos]['group'] = $categoryGroup;
            }
        }

        return $categories;
    }

    public function getFrontEndTags(array $settings): array
    {
        $tags = [];

        if (isset($settings['source'])) {
            $group = explode(':', $settings['source']);
            $pos = count($tags) + 1;

            if (count($group) == 2) {
                $tagGroup = Craft::$app->getTags()->getTagGroupByUid($group[1]);

                if ($tagGroup === null) {
                    Craft::warning("Unable to find Tag Group: $group[1]", __METHOD__);

                    return [];
                }

                $tags[$pos]['tags'] = Tag::find()->groupId($tagGroup->id)->all();
                $tags[$pos]['group'] = $tagGroup;
            }
        }

        return $tags;
    }

    private function getSinglesEntries(): array
    {
        $sections = Craft::$app->getSections()->getSectionsByType(Section::TYPE_SINGLE);
        $singles = [];

        foreach ($sections as $section) {
            $results = Entry::find()->sectionId($section->id)->orderBy(['title' => SORT_ASC])->all();
            $singles[] = $results[0];
        }

        return $singles;
    }
}
