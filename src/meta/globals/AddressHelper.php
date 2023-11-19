<?php

namespace BarrelStrength\Sprout\meta\globals;

use BarrelStrength\Sprout\meta\MetaModule;
use Craft;
use craft\elements\Address;
use craft\events\AuthorizationCheckEvent;
use craft\helpers\Cp;
use craft\models\Site;
use craft\services\Elements;
use yii\base\Event;
use yii\web\ForbiddenHttpException;

class AddressHelper
{
    /**
     * Ensures a user editing the Global Metadata address is authorized to do so
     */
    public static function registerEditAddressAuthorizationEvents(): void
    {
        if (!Craft::$app->getRequest()->getIsCpRequest()) {
            return;
        }

        $checkAuth = static function(AuthorizationCheckEvent $event) {

            $site = Cp::requestedSite();

            if (!$site instanceof Site) {
                throw new ForbiddenHttpException('User not authorized to edit content in any sites.');
            }

            // Get Site ID from CP request because we might have a new address without a Site ID in the db
            $globals = MetaModule::getInstance()->globalMetadata->getGlobalMetadata($site);

            /** @var Address $address */
            $address = $event->sender;
            $canonicalId = $address->getCanonicalId();

            if (
                $canonicalId &&
                $canonicalId === $globals->addressModel->id &&
                $event->user->can(MetaModule::p('editGlobals'))
            ) {
                $event->authorized = true;
                $event->handled = true;
            }
        };

        Event::on(Address::class, Elements::EVENT_AUTHORIZE_VIEW, $checkAuth);
        Event::on(Address::class, Elements::EVENT_AUTHORIZE_SAVE, $checkAuth);
    }
}
