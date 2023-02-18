<?php

namespace BarrelStrength\Sprout\redirects\redirects;

use BarrelStrength\Sprout\redirects\components\elements\RedirectElement;
use BarrelStrength\Sprout\redirects\RedirectsModule;
use Craft;
use craft\events\ExceptionEvent;
use craft\helpers\UrlHelper;
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

        $excludedUrlPatterns = $settings->getExcludedUrlPatterns($currentSite->id);

        if ($excludedUrlPatterns && RedirectHelper::isExcludedUrlPattern($absoluteUrl, $excludedUrlPatterns)) {
            return;
        }

        // Check if the requested URL needs to be redirected
        $redirect = RedirectHelper::findUrl($absoluteUrl, $currentSite);

        if (!$redirect && isset($settings->enable404RedirectLog) && $settings->enable404RedirectLog) {
            // Save new 404 Redirect
            $redirect = PageNotFoundHelper::save404Redirect($absoluteUrl, $currentSite, $settings);
        }

        if (!$redirect instanceof RedirectElement) {
            return;
        }

        RedirectHelper::incrementCount($redirect);

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
}
