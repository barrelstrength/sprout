<?php

namespace BarrelStrength\Sprout\redirects\components\elements\actions;

use BarrelStrength\Sprout\redirects\components\elements\RedirectElement;
use BarrelStrength\Sprout\redirects\redirects\RedirectHelper;
use Craft;
use craft\base\ElementAction;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Cp;
use craft\models\Site;
use yii\web\ForbiddenHttpException;

abstract class BaseStatusCodeAction extends ElementAction
{
    /**
     * The Element Index page source value
     */
    public string $source;

    /**
     * The Status Code the action will update a Redirect to use
     */
    abstract public function getStatusCode(): int;

    public function performAction(ElementQueryInterface $query): bool
    {
        $site = Cp::requestedSite();

        if (!$site instanceof Site) {
            throw new ForbiddenHttpException('User not authorized to edit content in any sites.');
        }

        $elementIds = $query->ids();

        $success = RedirectHelper::updateStatusCode($elementIds, $this->getStatusCode());

        if (!$success) {
            $this->setMessage(Craft::t('sprout-module-redirects', 'Unable to update Redirects.'));

            return false;
        }

        $this->setMessage(Craft::t('sprout-module-redirects', 'Redirects updated.'));

        // Without this, the old Redirect persists in the Element Index Table column
        Craft::$app->getElements()->invalidateCachesForElementType(RedirectElement::class);

        return true;
    }
}
