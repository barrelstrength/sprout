<?php

namespace BarrelStrength\Sprout\core\controllers\web;

use BarrelStrength\Sprout\core\modules\SettingsHelper;
use BarrelStrength\Sprout\core\modules\SproutModuleTrait;
use Craft;
use craft\web\Controller;
use craft\web\twig\variables\Cp as CpVariable;
use http\Exception\InvalidArgumentException;
use yii\web\Response;

class SettingsController extends Controller
{
    /**
     * Provides a generic way for Sprout modules to save settings to Project Config
     *
     * This action is designed to handle SIMPLE settings scenarios. Don't make this
     * more complex. Any advanced scenarios should route to their own controller.
     */
    public function actionSaveSettings(): ?Response
    {
        $this->requirePostRequest();

        $moduleId = Craft::$app->getRequest()->getBodyParam('moduleId');
        $settings = Craft::$app->getRequest()->getBodyParam('settings');

        /** @var SproutModuleTrait $module */
        $module = Craft::$app->getModule($moduleId);

        if (!$module) {
            throw new InvalidArgumentException('No module with the ID ' . $moduleId);
        }

        $moduleSettingsKey = $module::projectConfigPath();
        $configSettings = Craft::$app->getProjectConfig()->get($moduleSettingsKey) ?? [];

        $settingsModel = $module->createSettingsModel();

        if ($layoutSettings = $this->getFieldLayoutSettings()) {
            $settings['fieldLayouts'] = $layoutSettings;
        }

        $settings = array_merge($configSettings, $settings);

        $settingsModel->setAttributes($settings, false);

        if (!SettingsHelper::saveSettings($moduleSettingsKey, $settingsModel)) {
            $message = Craft::t('sprout-module-core', 'Couldn’t save settings.');
            Craft::$app->getSession()->setError($message);

            Craft::$app->getUrlManager()->setRouteParams([
                'settings' => $settings,
            ]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('sprout-module-core', 'Settings saved.'));

        return $this->redirectToPostedUrl();
    }

    /**
     * This method helps us redirect Sprout Module primary nav items
     * to the first subnav item as the first nav item may change based on
     * which modules are enabled and Craft's check isn't able to match our
     * selected items.
     *
     * Sprout Modules with variable 'subnav' should route to this action:
     * 'sprout/transactional' => 'sprout-module-core/settings/redirect-nav-item',
     *
     * https://github.com/craftcms/cms/blob/46bb9edda436b6dc6851b263fedbb5895e63edd1/src/web/twig/variables/Cp.php#L341
     */
    public function actionRedirectNavItem(): void
    {
        $cp = new CpVariable();

        $targetPath = Craft::$app->getRequest()->getPathInfo();

        $selectedNav = array_filter($cp->nav(), static function($item) use ($targetPath) {
            $array = explode('?', $item['url']);
            $hmm = reset($array);

            return str_ends_with($hmm, $targetPath);
        });

        $subNav = array_column($selectedNav, 'subnav');
        $firstSubNavItem = array_slice($subNav[0], 0, 1);
        $firstSubnavUrl = array_column($firstSubNavItem, 'url');

        $this->redirect($firstSubnavUrl[0]);
    }

    /**
     * Renders a preview of the a config override settings file
     *
     * @example /sprout/settings/preview/sprout-module-forms.php
     */
    public function actionPreviewConfigSettingsFile(): Response
    {
        $this->requireAdmin();

        $this->response->headers->set('Content-Type', 'text/plain');
        $this->response->format = Response::FORMAT_RAW;

        $file = Craft::$app->getConfig()->getConfigFilePath('sprout-module-data-studio');
        $this->response->data = file_get_contents($file);

        return $this->response;
    }

    /**
     * The generic, simple Settings support includes a single Field Layout
     * stored in the Project Config in the same format that Craft stores
     * field layouts.
     */
    private function getFieldLayoutSettings(): ?array
    {
        if (!Craft::$app->getRequest()->getBodyParam('fieldLayout')) {
            return [];
        }

        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();

        return [
            $fieldLayout->uid => $fieldLayout->getConfig(),
        ];
    }
}
