<?php

namespace BarrelStrength\Sprout\redirects\components\elements\actions;

use BarrelStrength\Sprout\core\Sprout;
use BarrelStrength\Sprout\redirects\components\elements\RedirectElement;
use BarrelStrength\Sprout\redirects\RedirectsModule;
use Craft;
use craft\base\ElementAction;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Cp;
use craft\models\Site;
use Throwable;
use yii\base\InvalidConfigException;
use yii\db\Transaction;
use yii\web\ForbiddenHttpException;

class ExcludeUrl extends ElementAction
{
    public function getTriggerLabel(): string
    {
        return Craft::t('sprout-module-redirects', 'Add to Excluded URLs');
    }

    public function performAction(ElementQueryInterface $query): bool
    {
        if (!$this->saveExcludedUrlPatternsFromElementAction($query)) {
            throw new InvalidConfigException('Unable to save Excluded URL Patterns');
        }

        $this->setMessage(Craft::t('sprout-module-redirects', 'Added to Excluded URL Patterns setting.'));

        return true;
    }

    public function saveExcludedUrlPatternsFromElementAction(ElementQueryInterface $query): bool
    {
        $site = Cp::requestedSite();

        if (!$site instanceof Site) {
            throw new ForbiddenHttpException('User not authorized to edit content in any sites.');
        }

        $redirectSettings = RedirectsModule::getInstance()->getSettings();

        /** @var RedirectElement[] $redirects */
        $redirects = $query->all();

        /** @var Transaction $transaction */
        $transaction = Craft::$app->db->beginTransaction();

        try {
            foreach ($redirects as $redirect) {
                $oldUrl = $redirect->oldUrl;

                // Append the selected Old URL to the Excluded URL Pattern settings array
                $excludedUrlPatterns = $redirectSettings->getSiteExcludedUrlPatterns($site->id);
                $excludedUrlPatterns .= PHP_EOL . $oldUrl;
                $redirectSettings->setExcludedUrlPatterns($excludedUrlPatterns);

                // Delete the old Redirect Element
                Craft::$app->elements->deleteElement($redirect, true);
            }

            $moduleId = RedirectsModule::getModuleId();
            $settings = [
                'siteExcludedUrlPatterns' => $redirectSettings->getSiteExcludedUrlPatterns($site->id),
            ];

            if (Sprout::getInstance()->coreSettings->saveDbSettings($moduleId, $settings, $site->id) === null) {
                return false;
            }

            $transaction->commit();

            return true;
        } catch (Throwable $throwable) {
            Craft::error($throwable->getMessage());

            $transaction->rollBack();

            return false;
        }
    }
}
