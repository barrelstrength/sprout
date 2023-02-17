<?php

namespace BarrelStrength\Sprout\redirects\redirects;

use BarrelStrength\Sprout\redirects\components\elements\RedirectElement;
use BarrelStrength\Sprout\redirects\db\SproutTable;
use BarrelStrength\Sprout\redirects\RedirectsModule;
use BarrelStrength\Sprout\redirects\RedirectsSettings;
use Craft;
use craft\db\Query;
use craft\db\Table;
use craft\events\ExceptionEvent;
use craft\helpers\UrlHelper;
use craft\models\Site;
use Exception;
use Twig\Error\RuntimeError as TwigRuntimeError;
use yii\base\Component;
use yii\web\HttpException;

class Redirects extends Component
{
    /**
     * Set to false to stop additional processing of redirects during this request
     */
    protected bool $processRedirect = true;

    public function handleRedirectsOnException(ExceptionEvent $event): void
    {
        $exception = $event->exception;
        $request = Craft::$app->getRequest();

        if ($exception instanceof TwigRuntimeError) {
            // Rendering Twig can generate a 404 also: i.e. {% exit 404 %}
            // If this is a Twig Runtime error, use the previous exception
            $exception = $exception->getPrevious();
        }

        // Check exception type first, the `redirects` settings below
        // may not exist if an error was triggered while they were loading
        if (!($exception instanceof HttpException && $exception->statusCode === StatusCode::PAGE_NOT_FOUND)) {
            return;
        }

        // Only handle front-end site requests that are not live preview
        if (!$request->getIsSiteRequest() || $request->getIsLivePreview()) {
            return;
        }

        // Avoid counting redirects twice when Sprout SEO and Redirects are
        // both installed and both call `handleRedirectsOnException` each request
        if (!$this->processRedirect) {
            return;
        }

        if (!RedirectsModule::isEnabled()) {
            return;
        }

        $this->processRedirectsForCurrentRequest();
    }

    public function processRedirectsForCurrentRequest(): void
    {
        $this->processRedirect = false;
        $request = Craft::$app->getRequest();
        $currentSite = Craft::$app->getSites()->getCurrentSite();

        $settings = RedirectsModule::getInstance()->getSettings();

        if ($settings->matchDefinition === MatchDefinition::URL_WITHOUT_QUERY_STRINGS) {
            $path = $request->getPathInfo();
            $absoluteUrl = UrlHelper::url($path);
        } else {
            $absoluteUrl = $request->getAbsoluteUrl();
        }

        if ($settings->getExcludedUrlPatterns($currentSite->id) && $this->isExcludedUrlPattern($absoluteUrl, $settings)) {
            return;
        }

        // Check if the requested URL needs to be redirected
        $redirect = RedirectsModule::getInstance()->redirects->findUrl($absoluteUrl, $currentSite);

        if (!$redirect && isset($settings->enable404RedirectLog) && $settings->enable404RedirectLog) {
            // Save new 404 Redirect
            $redirect = PageNotFoundHelper::save404Redirect($absoluteUrl, $currentSite, $settings);
        }

        if (!$redirect instanceof RedirectElement) {
            return;
        }

        RedirectsModule::getInstance()->redirects->incrementCount($redirect);

        if ($settings->queryStringStrategy === QueryStringStrategy::REMOVE_QUERY_STRINGS) {
            $queryString = '';
        } elseif ($settings->queryStringStrategy === QueryStringStrategy::APPEND_QUERY_STRINGS) {
            $queryString = '?' . $request->getQueryStringWithoutPath();
        } else {
            return;
        }

        if ($redirect->enabled && $redirect->statusCode !== StatusCode::PAGE_NOT_FOUND) {
            if (UrlHelper::isAbsoluteUrl($redirect->newUrl)) {
                Craft::$app->getResponse()->redirect(
                    $redirect->newUrl . $queryString, $redirect->statusCode
                );
            } else {
                Craft::$app->getResponse()->redirect(
                    $redirect->getAbsoluteNewUrl() . $queryString, $redirect->statusCode
                );
            }

            Craft::$app->end();
        }
    }

    /**
     * Find a regex url using the preg_match php function and replace
     * capture groups if any using the preg_replace php function also check normal urls
     */
    public function findUrl($absoluteUrl, Site $site): ?RedirectElement
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
    public function incrementCount(RedirectElement $redirect): bool
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

    public function isExcludedUrlPattern($absoluteUrl, RedirectsSettings $settings, int $siteId): bool
    {
        $excludedUrlPatterns = explode(PHP_EOL, $settings->getExcludedUrlPatterns($siteId));

        // Remove empty lines and comments
        $excludedUrlPatterns = array_filter($excludedUrlPatterns, static fn($excludedUrlPattern): bool => !empty($excludedUrlPattern) && !str_starts_with($excludedUrlPattern, '#'));

        foreach ($excludedUrlPatterns as $excludedUrlPattern) {
            if (str_starts_with($excludedUrlPattern, '#')) {
                continue;
            }

            // Use backticks as delimiters as they are invalid characters for URLs
            $excludedUrlPattern = '`' . $excludedUrlPattern . '`';

            if (preg_match($excludedUrlPattern, $absoluteUrl)) {
                return true;
            }
        }

        return false;
    }
}
