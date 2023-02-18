<?php

namespace BarrelStrength\Sprout\redirects\redirects;

use BarrelStrength\Sprout\core\jobs\PurgeElementHelper;
use BarrelStrength\Sprout\core\jobs\PurgeElements;
use BarrelStrength\Sprout\redirects\components\elements\RedirectElement;
use BarrelStrength\Sprout\redirects\db\SproutTable;
use BarrelStrength\Sprout\redirects\RedirectsModule;
use BarrelStrength\Sprout\redirects\RedirectsSettings;
use Craft;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\DateTimeHelper;
use craft\models\Site;

class PageNotFoundHelper
{
    public static function save404Redirect($absoluteUrl, Site $site, RedirectsSettings $settings): ?RedirectElement
    {
        $request = Craft::$app->getRequest();

        $redirect = new RedirectElement();

        $baseUrl = Craft::getAlias($site->getBaseUrl());

        $baseUrlMatch = mb_strpos($absoluteUrl, $baseUrl) === 0;

        if (!$baseUrlMatch) {
            return null;
        }

        // Strip the base URL from our Absolute URL
        // We need to do this because the Base URL can contain
        // subfolders that are included in the path and we only
        // want to store the path value that doesn't include
        // the Base URL
        $uri = substr($absoluteUrl, strlen($baseUrl));

        $redirect->oldUrl = $uri;
        $redirect->newUrl = '/';
        $redirect->statusCode = StatusCode::PAGE_NOT_FOUND;
        $redirect->matchStrategy = MatchStrategy::EXACT_MATCH;
        $redirect->enabled = 0;
        $redirect->count = 0;
        $redirect->siteId = $site->id;
        $redirect->lastRemoteIpAddress = $settings->trackRemoteIp ? $request->getRemoteIp() : null;
        $redirect->lastReferrer = $request->getReferrer();
        $redirect->lastUserAgent = $request->getUserAgent();
        $redirect->dateLastUsed = DateTimeHelper::now();

        if (!Craft::$app->elements->saveElement($redirect)) {
            Craft::warning($redirect->getErrors(), __METHOD__);

            return null;
        }

        self::purge404s([$redirect->getId()], $site->id);

        return $redirect;
    }

    public static function remove404RedirectIfExists(RedirectElement $redirect): void
    {
        $existing404RedirectId = (new Query())
            ->select('redirects.id')
            ->from(['redirects' => SproutTable::REDIRECTS])
            ->innerJoin(Table::ELEMENTS_SITES . ' elements_sites', '[[elements_sites.elementId]] = [[redirects.id]]')
            ->where([
                'elements_sites.siteId' => $redirect->siteId,
                'redirects.oldUrl' => $redirect->oldUrl,
                'redirects.statusCode' => StatusCode::PAGE_NOT_FOUND,
            ])
            ->scalar();

        // Don't delete the 404 if we're currently updating it
        if (!$existing404RedirectId || (int)$existing404RedirectId === $redirect->getId()) {
            return;
        }

        if ($element = Craft::$app->getElements()->getElementById($existing404RedirectId)) {
            Craft::$app->getElements()->deleteElement($element, true);
        }
    }

    public static function purge404s(array $excludedIds = [], $siteId = null, bool $force = false): void
    {
        $redirectSettings = RedirectsModule::getInstance()->getSettings();
        $probability = $redirectSettings->cleanupProbability;

        // See Craft Garbage collection treatment of probability
        // https://docs.craftcms.com/v3/gc.html
        if (!$force && random_int(0, 1_000_000) >= $probability) {
            return;
        }

        /// Loop through all Sites if we don't have a specific site to target
        $siteIds = $siteId === null ? Craft::$app->getSites()->getAllSiteIds() : [$siteId];

        foreach ($siteIds as $currentSiteId) {

            $query = RedirectElement::find()
                ->where(['statusCode' => StatusCode::PAGE_NOT_FOUND])
                ->andWhere(['siteId' => $currentSiteId]);

            // Don't delete these Redirects
            if (!empty($excludedIds)) {
                $query->andWhere(['not in', 'sprout_redirects.id', $excludedIds]);
            }

            // orderBy works as string but doesn't recognize second DESC setting as array
            $query->orderBy('sprout_redirects.count DESC, sprout_redirects.dateUpdated DESC')
                ->status(null);

            $ids = $query->ids();

            $limitAdjustment = empty($excludedIds) ? 0 : 1;
            $idsToDelete = array_slice($ids, $redirectSettings->total404Redirects - $limitAdjustment);

            if (!empty($idsToDelete)) {

                $batchSize = 25;

                // Leave second argument blank and bust loop with break statement. Really. It's in the docs.
                // https://www.php.net/manual/en/control-structures.for.php
                for ($i = 0; ; $i++) {

                    // Get me a list of the IDs to delete for this iteration. If less
                    // than the batchSize, that specific number will be returned
                    $loopedIdsToDelete = array_slice($idsToDelete, ($i * $batchSize) + 1, $batchSize);

                    // Adjust final batch so we don't add 1
                    if (count($loopedIdsToDelete) < $batchSize) {
                        $loopedIdsToDelete = array_slice($idsToDelete, $i * $batchSize, $batchSize);
                    }

                    // End the for loop once we don't find any more ids in our current offset
                    if (empty($loopedIdsToDelete)) {
                        break;
                    }

                    // Create a job for this batch
                    $excludedIds ??= [];
                    // Call the delete redirects job, give it some delay so we don't demand
                    // all the server resources. This is most important if anybody changes the
                    // Redirect Limit setting in a massive way
                    $delay = ($i - 1) * 20;

                    $purgeElements = new PurgeElements();
                    $purgeElements->elementType = RedirectElement::class;
                    $purgeElements->siteId = $currentSiteId;
                    $purgeElements->idsToDelete = $loopedIdsToDelete;
                    $purgeElements->idsToExclude = $excludedIds;

                    PurgeElementHelper::purgeElements($purgeElements, $delay);
                }
            }
        }
    }
}
