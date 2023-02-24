<?php

namespace BarrelStrength\Sprout\mailer\components\elements\subscriber;

use BarrelStrength\Sprout\mailer\components\audiences\SubscriberListAudienceType;
use BarrelStrength\Sprout\mailer\components\elements\audience\AudienceElement;
use BarrelStrength\Sprout\mailer\components\elements\subscriber\fieldlayoutelements\EmailField;
use BarrelStrength\Sprout\mailer\MailerModule;
use Craft;
use craft\elements\actions\DeleteUsers;
use craft\elements\actions\Edit;
use craft\elements\db\UserQuery;
use craft\elements\User;
use craft\helpers\Html;
use craft\helpers\UrlHelper;

/**
 * @property array $listIds
 * @property array $lists
 */
class SubscriberElement extends User
{
    public static function displayName(): string
    {
        return Craft::t('sprout-module-mailer', 'Subscriber');
    }

    public static function lowerDisplayName(): string
    {
        return Craft::t('sprout-module-mailer', 'subscriber');
    }

    public static function pluralDisplayName(): string
    {
        return Craft::t('sprout-module-mailer', 'Subscribers');
    }

    public static function pluralLowerDisplayName(): string
    {
        return Craft::t('sprout-module-mailer', 'subscribers');
    }

    /**
     * @return SubscriberElementQuery The newly created [[SubscriberQuery]] instance.
     */
    public static function find(): SubscriberElementQuery
    {
        return new SubscriberElementQuery(static::class);
    }

    protected static function defineSources(string $context = null): array
    {
        $sources[] = [
            'key' => '*',
            'label' => Craft::t('sprout-module-mailer', 'All subscribers'),
            'data' => [
                'slug' => 'all',
            ],
            'defaultSort' => ['dateCreated', 'desc'],
        ];

        /** @var AudienceElement[] $lists */
        $lists = AudienceElement::find()
            ->audienceType(SubscriberListAudienceType::class)
            ->all();

        if (!empty($lists)) {
            $sources[] = [
                'heading' => Craft::t('sprout-module-mailer', 'Subscriber Lists'),
            ];

            foreach ($lists as $list) {
                $source = [
                    'key' => 'lists:' . $list->getId(),
                    'label' => $list->name,
                    'data' => [
                        'handle' => $list->handle,
                    ],
                    'criteria' => [
                        'listId' => $list->getId(),
                    ],
                ];

                $sources[] = $source;
            }
        }

        $user = Craft::$app->getUser()->getIdentity();

        if ($user->admin && $user->can('editUsers')) {
            $sources[] = [
                'heading' => Craft::t('app', 'All Users'),
            ];

            $sources[] = [
                'key' => 'credentialed',
                'label' => Craft::t('app', 'Credentialed'),
                'criteria' => [
                    'status' => UserQuery::STATUS_CREDENTIALED,
                    'skipSubscriberElementQuery' => true,
                ],
                'hasThumbs' => true,
                'data' => [
                    'slug' => 'credentialed',
                ],
            ];
            $sources[] = [
                'key' => 'inactive',
                'label' => Craft::t('app', 'Inactive'),
                'criteria' => [
                    'status' => self::STATUS_INACTIVE,
                    'skipSubscriberElementQuery' => true,
                ],
                'hasThumbs' => true,
                'data' => [
                    'slug' => 'inactive',
                ],
            ];
        }

        return $sources;
    }
    
    protected static function defineTableAttributes(): array
    {
        return [
            'firstName' => ['label' => Craft::t('sprout-module-mailer', 'First Name')],
            'lastName' => ['label' => Craft::t('sprout-module-mailer', 'Last Name')],
            'dateCreated' => ['label' => Craft::t('sprout-module-mailer', 'Date Created')],
            'dateUpdated' => ['label' => Craft::t('sprout-module-mailer', 'Date Updated')],
        ];
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        return [
            'fullName',
            'dateCreated',
            'lastLoginDate',
        ];
    }

    protected static function defineActions(string $source = null): array
    {
        $actions = [];
        $elementsService = Craft::$app->getElements();

        $actions[] = $elementsService->createAction([
            'type' => Edit::class,
            'label' => Craft::t('app', 'Edit user'),
        ]);

        if (Craft::$app->getUser()->checkPermission('deleteUsers')) {
            // Delete
            $actions[] = DeleteUsers::class;
        }

        return $actions;
    }

    protected static function defineSortOptions(): array
    {
        if (Craft::$app->getConfig()->getGeneral()->useEmailAsUsername) {
            $attributes = [
                'email' => Craft::t('app', 'Email'),
                'fullName' => Craft::t('app', 'Full Name'),
                'firstName' => Craft::t('app', 'First Name'),
                'lastName' => Craft::t('app', 'Last Name'),
                [
                    'label' => Craft::t('app', 'Last Login'),
                    'orderBy' => 'lastLoginDate',
                    'defaultDir' => 'desc',
                ],
                [
                    'label' => Craft::t('app', 'Date Created'),
                    'orderBy' => 'elements.dateCreated',
                    'attribute' => 'dateCreated',
                    'defaultDir' => 'desc',
                ],
                [
                    'label' => Craft::t('app', 'Date Updated'),
                    'orderBy' => 'elements.dateUpdated',
                    'attribute' => 'dateUpdated',
                    'defaultDir' => 'desc',
                ],
                [
                    'label' => Craft::t('app', 'ID'),
                    'orderBy' => 'elements.id',
                    'attribute' => 'id',
                ],
            ];
        } else {
            $attributes = [
                'email' => Craft::t('app', 'Email'),
                'fullName' => Craft::t('app', 'Full Name'),
                'firstName' => Craft::t('app', 'First Name'),
                'lastName' => Craft::t('app', 'Last Name'),
                [
                    'label' => Craft::t('app', 'Date Created'),
                    'orderBy' => 'elements.dateCreated',
                    'attribute' => 'dateCreated',
                    'defaultDir' => 'desc',
                ],
                [
                    'label' => Craft::t('app', 'Date Updated'),
                    'orderBy' => 'elements.dateUpdated',
                    'attribute' => 'dateUpdated',
                    'defaultDir' => 'desc',
                ],
                [
                    'label' => Craft::t('app', 'ID'),
                    'orderBy' => 'elements.id',
                    'attribute' => 'id',
                ],
            ];
        }

        return $attributes;
    }

    public function __toString(): string
    {
        return $this->email;
    }

    protected function tableAttributeHtml(string $attribute): string
    {
        /** @noinspection DegradedSwitchInspection */
        switch ($attribute) {
            case 'email':
                return $this->email ? Html::mailto(Html::encode($this->email)) : '';
        }

        return parent::tableAttributeHtml($attribute);
    }

    protected function uiLabel(): ?string
    {
        return $this->email;
    }

    protected function cpEditUrl(): ?string
    {
        return UrlHelper::cpUrl('sprout/email/subscribers/edit/' . $this->id);
    }

    public function canView(User $user): bool
    {
        return $user->can(MailerModule::p('editSubscribers'));
    }

    public function canSave(User $user): bool
    {
        return $user->can(MailerModule::p('editSubscribers'));
    }

    public function canDelete(User $user): bool
    {
        return $user->can(MailerModule::p('editSubscribers'));
    }

    public function canDuplicate(User $user): bool
    {
        return $user->can(MailerModule::p('editSubscribers'));
    }

    public function canCreateDrafts(User $user): bool
    {
        return false;
    }
}
