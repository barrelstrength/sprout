<?php

namespace BarrelStrength\Sprout\redirects\redirects;

use BarrelStrength\Sprout\redirects\components\elements\RedirectElement;
use BarrelStrength\Sprout\redirects\db\SproutTable;
use BarrelStrength\Sprout\redirects\RedirectsModule;
use Craft;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Html;
use craft\helpers\Template;
use craft\helpers\UrlHelper;
use craft\models\Site;
use Exception;
use Twig\Markup;

class RedirectHelper
{
    public const SLASH_CHARACTER = '/';

    public static function removeSlash(string $uri = null): ?string
    {
        if ($uri === null) {
            return null;
        }

        // If the first character of the URI is a slash, remove it
        if (isset($uri[0]) && $uri[0] == self::SLASH_CHARACTER) {
            $uri = ltrim($uri, self::SLASH_CHARACTER);
        }

        return $uri;
    }

    public static function getStructureId(): int
    {
        return RedirectsModule::getInstance()->getSettings()->getStructureId();
    }

    /**
     * Update the current statusCode in the record
     */
    public static function updateStatusCode($ids, $statusCode): int
    {
        return Craft::$app->db->createCommand()->update(
            SproutTable::REDIRECTS,
            ['statusCode' => $statusCode],
            ['in', 'id', $ids]
        )->execute();
    }

    /**
     * Find a regex url using the preg_match php function and replace
     * capture groups if any using the preg_replace php function also check normal urls
     */
    public static function findUrl($absoluteUrl, Site $site): ?RedirectElement
    {
        $absoluteUrl = urldecode($absoluteUrl);
        $baseSiteUrl = Craft::getAlias($site->getBaseUrl());

        $allRedirects = (new Query())
            ->select([
                'redirects.id',
                'redirects.oldUrl',
                'redirects.newUrl',
                'redirects.statusCode',
                'redirects.matchStrategy',
                'redirects.count',
                'elements.enabled',
                'elements_sites.siteId',
            ])
            ->from(['redirects' => SproutTable::REDIRECTS])
            ->leftJoin(['elements' => Table::ELEMENTS], '[[redirects.id]] = [[elements.id]]')
            ->leftJoin(['elements_sites' => Table::ELEMENTS_SITES], '[[redirects.id]] = [[elements_sites.elementId]]')
            ->leftJoin(['structureelements' => Table::STRUCTUREELEMENTS], '[[redirects.id]] = [[structureelements.elementId]]')
            ->orderBy('[[structureelements.lft]] asc')
            ->where([
                '[[elements_sites.siteId]]' => $site->id,
                '[[structureelements.level]]' => 1,
            ])
            ->all();

        if (!$allRedirects) {
            return null;
        }

        $redirects = [];
        $pageNotFoundRedirects = [];

        foreach ($allRedirects as $redirect) {
            if ($redirect['statusCode'] === StatusCode::PAGE_NOT_FOUND) {
                $pageNotFoundRedirects[] = $redirect;
            } else {
                $redirects[] = $redirect;
            }
        }

        // Group all 404 Redirects at the end of the array
        $orderedRedirects = [...$redirects, ...$pageNotFoundRedirects];

        /**
         * @var RedirectElement $redirect
         */
        foreach ($orderedRedirects as $redirect) {

            if ($redirect['matchStrategy'] === MatchStrategy::REGEX_MATCH) {
                // Use backticks as delimiters as they are invalid characters for URLs
                $oldUrlPattern = '`' . $redirect['oldUrl'] . '`';
                // Remove the base URL so we just have the relative path for the redirect
                $currentPath = preg_replace('`^' . $baseSiteUrl . '`', '', $absoluteUrl);
                if (preg_match($oldUrlPattern, $currentPath)) {

                    // Make sure URLs that redirect to another domain end in a slash
                    if ($redirect['newUrl'] !== null && UrlHelper::isAbsoluteUrl($redirect['newUrl'])) {
                        $newUrl = parse_url($redirect['newUrl']);

                        // If path is set, we know that the base domain has a slash before the path
                        if (isset($newUrl['path'])) {
                            $newUrlPattern = $redirect['newUrl'];
                        } elseif (!str_contains($newUrl['host'], '$')) {
                            $newUrlPattern = $redirect['newUrl'] . '/';
                        } else {

                            // If the hostname has a $ it probably uses a a capture group
                            // and is going to generate an invalid new URL when using it
                            // as at this point it doesn't appear to have a path
                            $invalidNewUrlMessage = 'The New URL value "' . $redirect['newUrl'] . '" in Redirect ID ' . $redirect['id'] . ' needs to be updated. The host name (' . $newUrl['host'] . ') of an absolute URL cannot contain capture groups and must end with a slash.';
                            Craft::error($invalidNewUrlMessage, __METHOD__);
                            Craft::$app->getDeprecator()->log('Target New URL is invalid.', $invalidNewUrlMessage);

                            // End the request, to avoid potential Open Redirect security issue
                            return null;
                        }
                    } else {
                        // We have a relative path
                        $newUrlPattern = $redirect['newUrl'];
                    }

                    // Replace capture groups if any
                    $redirect['newUrl'] = preg_replace($oldUrlPattern, $newUrlPattern, $currentPath);

                    return new RedirectElement($redirect);
                }
            } elseif ($baseSiteUrl . $redirect['oldUrl'] === $absoluteUrl) {
                // Update null value to return home page
                $redirect['newUrl'] ??= '/';

                return new RedirectElement($redirect);
            }
        }

        return null;
    }

    /**
     * Increments the count of a redirect when hit
     */
    public static function incrementCount(RedirectElement $redirect): bool
    {
        try {
            $count = ++$redirect->count;

            Craft::$app->db->createCommand()->update(SproutTable::REDIRECTS,
                ['count' => $count],
                ['id' => $redirect->getId()]
            )->execute();
        } catch (Exception $exception) {
            Craft::error('Unable to increment redirect: ' . $exception->getMessage(), __METHOD__);
        }

        return true;
    }

    public static function getNewRedirectButtonHtml(Site $site): Markup
    {
        $label = Craft::t('sprout-module-redirects', 'New Redirect');
        $url = UrlHelper::cpUrl('sprout/redirects/new', [
            'site' => $site->handle,
        ]);

        $html = Html::a($label, $url, [
            'class' => ['btn', 'submit', 'add', 'icon'],
            'id' => 'sprout-redirects-new-button',
        ]);

        return Template::raw($html);
    }

    public static function isExcludedUrlPattern($absoluteUrl, array $excludedUrlPatterns): bool
    {
        foreach ($excludedUrlPatterns as $excludedUrlPattern) {

            // Use backticks as delimiters as they are invalid characters for URLs
            $excludedUrlPattern = '`' . $excludedUrlPattern . '`';

            if (preg_match($excludedUrlPattern, $absoluteUrl)) {
                return true;
            }
        }

        return false;
    }

    public static function getExcludeUrlsButtonHtml(Site $site): Markup
    {
        $label = Craft::t('sprout-module-redirects', 'Excluded URLs');
        $url = UrlHelper::cpUrl('sprout/redirects/settings', [
            'site' => $site->handle,
        ]);

        $html = Html::a($label, $url, [
            'class' => ['btn', 'settings', 'icon'],
        ]);

        return Template::raw($html);
    }
}
