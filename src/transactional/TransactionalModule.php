<?php

namespace BarrelStrength\Sprout\transactional;

use BarrelStrength\Sprout\core\db\MigrationTrait;
use BarrelStrength\Sprout\core\editions\EditionTrait;
use BarrelStrength\Sprout\core\modules\SproutModuleTrait;
use BarrelStrength\Sprout\core\modules\TranslatableTrait;
use BarrelStrength\Sprout\core\relations\RelationsHelper;
use BarrelStrength\Sprout\core\Sprout;
use BarrelStrength\Sprout\core\twig\SproutVariable;
use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\mailer\email\EmailTypes;
use BarrelStrength\Sprout\mailer\MailerModule;
use BarrelStrength\Sprout\transactional\components\elements\TransactionalEmailElement;
use BarrelStrength\Sprout\transactional\components\emailtypes\TransactionalEmailEmailType;
use BarrelStrength\Sprout\transactional\notificationevents\NotificationEventHelper;
use BarrelStrength\Sprout\transactional\notificationevents\NotificationEvents;
use Craft;
use craft\base\conditions\BaseCondition;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\services\UserPermissions;
use craft\web\Application;
use craft\web\UrlManager;
use craft\web\View;
use yii\base\Event;
use yii\base\Module;

/**
 * @property NotificationEvents $notificationEvents
 */
class TransactionalModule extends Module
{
    use SproutModuleTrait;
    use EditionTrait;
    use MigrationTrait;
    use TranslatableTrait;

    public static function getInstance(): TransactionalModule
    {
        MailerModule::getInstance();

        /** @var TransactionalModule $module */
        $module = Sprout::getSproutModule(static::class, 'sprout-module-transactional');

        return $module;
    }

    public static function getDisplayName(): string
    {
        $displayName = Craft::t('sprout-module-core', 'Transactional');

        return $displayName;
    }

    public static function getShortName(): string
    {
        return 'transactional';
    }

    public static function getDescription(): string
    {
        return Craft::t('sprout-module-core', 'Manage and send notifications');
    }

    public static function getUpgradeMessage(): string
    {
        return Craft::t('sprout-module-core', 'Upgrade to Sprout Email PRO to send personalized notification emails using unlimited Notification Events');
    }

    public function init(): void
    {
        parent::init();

        $this->registerTranslations();

        $this->setComponents([
            'notificationEvents' => NotificationEvents::class,
        ]);

        Craft::setAlias('@BarrelStrength/Sprout/transactional', __DIR__);

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function(RegisterUrlRulesEvent $event): void {
                $event->rules = array_merge($event->rules, $this->getCpUrlRules());
            });

        Event::on(
            View::class,
            View::EVENT_REGISTER_CP_TEMPLATE_ROOTS,
            function(RegisterTemplateRootsEvent $e): void {
                $e->roots['sprout-module-transactional'] = $this->getBasePath() . DIRECTORY_SEPARATOR . 'templates';
            });

        Event::on(
            SproutVariable::class,
            SproutVariable::EVENT_INIT,
            function(Event $event): void {
                $event->sender->registerModule($this);
            });

        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            function(RegisterUserPermissionsEvent $event): void {
                $event->permissions[] = [
                    'heading' => Craft::t('sprout-module-transactional', 'Sprout Module | Transactional'),
                    'permissions' => $this->getUserPermissions(),
                ];
            });

        Event::on(
            Application::class,
            Application::EVENT_INIT,
            [$this->notificationEvents, 'registerNotificationEventHandlers']
        );

        Event::on(
            BaseCondition::class,
            BaseCondition::EVENT_REGISTER_CONDITION_RULE_TYPES,
            [$this->notificationEvents, 'registerConditionRuleTypes']
        );

        Event::on(
            EmailTypes::class,
            EmailTypes::EVENT_REGISTER_PACKAGE_TYPES,
            static function(RegisterComponentTypesEvent $event) {
                $event->types[] = TransactionalEmailEmailType::class;
            }
        );

        Event::on(
            EmailElement::class,
            EmailElement::EVENT_AFTER_SAVE,
            [$this->notificationEvents, 'handleActiveNotificationEventSettings']
        );

        Event::on(
            RelationsHelper::class,
            RelationsHelper::EVENT_REGISTER_SPROUT_SOURCE_RELATIONS_ELEMENT_TYPES,
            static function(RegisterComponentTypesEvent $event) {
                $event->types[] = TransactionalEmailElement::class;
            }
        );

        Event::on(
            RelationsHelper::class,
            RelationsHelper::EVENT_ADD_SPROUT_SOURCE_ELEMENT_RELATIONS,
            [NotificationEventHelper::class, 'getSourceElementRelations'], [
                'sourceElementType' => TransactionalEmailElement::class,
            ]
        );

        Event::on(
            EmailElement::class,
            EmailElement::EVENT_DEFINE_BEHAVIORS,
            [NotificationEventHelper::class, 'attachBehaviors']
        );
    }

    public function getCpUrlRules(): array
    {
        return [
            'sprout/email' =>
                'sprout-module-core/settings/redirect-nav-item',

            // Transactional Email Package
            'sprout/email/<emailType:transactional-email>/edit/<elementId:\d+>' =>
                'elements/edit',
            'sprout/email/<emailType:transactional-email>/new' =>
                'sprout-module-mailer/email/create-email',
            'sprout/email/<emailType:transactional-email>' =>
                'sprout-module-mailer/email/email-index-template',

            // Welcome
            'sprout/welcome/transactional-email' => [
                'template' => 'sprout-module-transactional/_admin/welcome',
            ],
            'sprout/upgrade/transactional-email' => [
                'template' => 'sprout-module-transactional/_admin/upgrade',
            ],
        ];
    }

    public function getUserPermissions(): array
    {
        return [
            self::p('viewTransactionalEmail') => [
                'label' => Craft::t('sprout-module-transactional', 'View Notification Emails'),
                'nested' => [
                    self::p('editTransactionalEmail') => [
                        'label' => Craft::t('sprout-module-transactional', 'Edit Notification Emails'),
                    ],
                ],
            ],
        ];
    }
}
