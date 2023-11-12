<?php

namespace BarrelStrength\Sprout\transactional;

use BarrelStrength\Sprout\core\db\MigrationInterface;
use BarrelStrength\Sprout\core\db\MigrationTrait;
use BarrelStrength\Sprout\core\editions\EditionTrait;
use BarrelStrength\Sprout\core\modules\SproutModuleInterface;
use BarrelStrength\Sprout\core\modules\SproutModuleTrait;
use BarrelStrength\Sprout\core\modules\TranslatableTrait;
use BarrelStrength\Sprout\core\relations\RelationsHelper;
use BarrelStrength\Sprout\core\Sprout;
use BarrelStrength\Sprout\core\twig\SproutVariable;
use BarrelStrength\Sprout\forms\components\elements\FormElement;
use BarrelStrength\Sprout\forms\controllers\FormTypesController;
use BarrelStrength\Sprout\mailer\emailtypes\EmailTypeHelper;
use BarrelStrength\Sprout\mailer\MailerModule;
use BarrelStrength\Sprout\mailer\mailers\Mailers;
use BarrelStrength\Sprout\transactional\components\elements\TransactionalEmailElement;
use BarrelStrength\Sprout\transactional\components\emailvariants\TransactionalEmailVariant;
use BarrelStrength\Sprout\transactional\components\formfeatures\TransactionalFormFeature;
use BarrelStrength\Sprout\transactional\components\mailers\TransactionalMailer;
use BarrelStrength\Sprout\transactional\notificationevents\NotificationEvents;
use Craft;
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
class TransactionalModule extends Module implements SproutModuleInterface, MigrationInterface
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
            Mailers::class,
            Mailers::INTERNAL_SPROUT_EVENT_REGISTER_MAILERS,
            static function(RegisterComponentTypesEvent $e): void {
                $e->types[] = TransactionalMailer::class;
            });

        Event::on(
            RelationsHelper::class,
            RelationsHelper::EVENT_REGISTER_SOURCE_RELATIONS_ELEMENT_TYPES,
            static function(RegisterComponentTypesEvent $event) {
                $event->types[] = TransactionalEmailElement::class;
            }
        );

        Event::on(
            FormElement::class,
            FormElement::INTERNAL_SPROUT_EVENT_REGISTER_FORM_FEATURE_TABS,
            [TransactionalFormFeature::class, 'registerTransactionalTab']
        );

        Event::on(
            FormTypesController::class,
            FormTypesController::INTERNAL_SPROUT_EVENT_DEFINE_FORM_FEATURE_SETTINGS,
            [TransactionalFormFeature::class, 'defineFormTypeSettings']
        );
    }

    public function getCpUrlRules(): array
    {
        return [
            'sprout/email' =>
                'sprout-module-core/settings/redirect-nav-item',

            // Transactional Email Package
            'sprout/email/transactional-email/edit/<elementId:\d+>' =>
                'elements/edit',
            'sprout/email/transactional-email/new' => [
                'route' => 'sprout-module-mailer/email/create-email',
                'params' => [
                    'emailVariant' => TransactionalEmailVariant::class,
                ],
            ],
            'sprout/email/transactional-email' => [
                'route' => 'sprout-module-mailer/email/email-index-template',
                'params' => [
                    'emailVariant' => TransactionalEmailVariant::class,
                ],
            ],

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
        $emailTypes = EmailTypeHelper::getEmailTypes();

        $permissions = [];
        foreach ($emailTypes as $emailType) {
            $nestedPermissions = [];
            $nestedPermissions[self::p('createTransactionalEmail:' . $emailType->uid)] = [
                'label' => Craft::t('sprout-module-transactional', 'Create email types'),
            ];
            $nestedPermissions[self::p('deleteTransactionalEmail:' . $emailType->uid)] = [
                'label' => Craft::t('sprout-module-transactional', 'Delete email types'),
            ];

            $permissions[self::p('editTransactionalEmail:' . $emailType->uid)] = [
                'label' => Craft::t('sprout-module-transactional', 'Edit "' . $emailType->name . '" email type'),
                'nested' => $nestedPermissions,
            ];
        }

        return $permissions;
    }
}
