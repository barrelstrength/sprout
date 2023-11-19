<?php

namespace BarrelStrength\Sprout\meta\globals;

use BarrelStrength\Sprout\meta\MetaModule;
use Craft;
use craft\elements\Address;
use craft\events\AuthorizationCheckEvent;
use craft\services\Elements;
use yii\base\Event;

class AddressHelper
{
    public static function registerEditAddressAuthorizationEvents(): void
    {
        if (!Craft::$app->getRequest()->getIsCpRequest()) {
            return;
        }

        $checkAuth = static function(AuthorizationCheckEvent $event) {

            /** @var Address $address */
            $address = $event->sender;
            $canonicalId = $address->getCanonicalId();
            $globals = MetaModule::getInstance()->globalMetadata->getGlobalMetadata();

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
