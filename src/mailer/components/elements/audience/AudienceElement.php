<?php

namespace BarrelStrength\Sprout\mailer\components\elements\audience;

use BarrelStrength\Sprout\core\sourcegroups\SourceGroupTrait;
use BarrelStrength\Sprout\mailer\audience\AudienceType;
use BarrelStrength\Sprout\mailer\components\elements\audience\fieldlayoutelements\AudienceHandleField;
use BarrelStrength\Sprout\mailer\components\elements\audience\fieldlayoutelements\AudienceNameField;
use BarrelStrength\Sprout\mailer\components\elements\audience\fieldlayoutelements\AudienceSettingsField;
use BarrelStrength\Sprout\mailer\db\SproutTable;
use BarrelStrength\Sprout\mailer\MailerModule;
use BarrelStrength\Sprout\mailer\subscribers\SubscriberHelper;
use BarrelStrength\Sprout\mailer\subscriptions\Subscription;
use Craft;
use craft\base\Element;
use craft\db\Query;
use craft\elements\actions\Delete;
use craft\elements\User;
use craft\errors\ElementNotFoundException;
use craft\events\DefineFieldLayoutFieldsEvent;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\validators\SlugValidator;
use craft\validators\UniqueValidator;
use craft\web\CpScreenResponseBehavior;
use Throwable;
use yii\web\ErrorHandler;
use yii\web\Response;

/**
 * @property mixed $listType
 */
class AudienceElement extends Element
{
    use SourceGroupTrait;

    public ?int $elementId = null;

    public ?string $audienceType = null;

    public string $name = '';

    public string $handle = '';

    public int $count = 0;

    public ?array $audienceSettings = [];

    public function __construct($config = [])
    {
        $this->audienceSettings = Json::decodeIfJson($config['audienceSettings'] ?? []);

        parent::__construct($config);
    }

    public static function displayName(): string
    {
        return Craft::t('sprout-module-mailer', 'Audience');
    }

    public static function lowerDisplayName(): string
    {
        return Craft::t('sprout-module-mailer', 'audience');
    }

    public static function pluralDisplayName(): string
    {
        return Craft::t('sprout-module-mailer', 'Audiences');
    }

    public static function pluralLowerDisplayName(): string
    {
        return Craft::t('sprout-module-mailer', 'audiences');
    }

    public static function hasStatuses(): bool
    {
        return true;
    }

    public static function defineNativeFields(DefineFieldLayoutFieldsEvent $event): void
    {
        /** @var FieldLayout $fieldLayout */
        $fieldLayout = $event->sender;

        if ($fieldLayout->type === self::class) {
            $event->fields[] = AudienceNameField::class;
            $event->fields[] = AudienceHandleField::class;
            $event->fields[] = AudienceSettingsField::class;
        }
    }

    public static function find(): AudienceElementQuery
    {
        return new AudienceElementQuery(static::class);
    }

    public static function defaultTableAttributes(string $source): array
    {
        return [
            'type',
            'manage',
            'count',
        ];
    }

    protected static function defineSources(string $context = null): array
    {
        $sources = [
            [
                'key' => '*',
                'label' => Craft::t('sprout-module-mailer', 'All audiences'),
            ],
        ];

        $groups = self::getSourceGroups();

        foreach ($groups as $group) {
            $key = 'group:' . $group->id;

            $sources[] = [
                'key' => $key,
                'label' => Craft::t('sprout-module-mailer', $group->name),
                'data' => ['id' => $group->id],
                'criteria' => ['groupId' => $group->id],
            ];
        }

        return $sources;
    }

    protected static function defineSortOptions(): array
    {
        return [
            'name' => Craft::t('sprout-module-mailer', 'Name'),
            [
                'label' => Craft::t('sprout-module-mailer', 'Count'),
                'orderBy' => 'count',
                'attribute' => 'count',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('sprout-module-mailer', 'Date Updated'),
                'orderBy' => 'elements.dateUpdated',
                'attribute' => 'dateUpdated',
                'defaultDir' => 'desc',
            ],
        ];
    }

    protected static function defineTableAttributes(): array
    {
        return [
            'name' => ['label' => Craft::t('sprout-module-mailer', 'Name')],
            'handle' => ['label' => Craft::t('sprout-module-mailer', 'List Handle')],
            'id' => ['label' => Craft::t('sprout-module-mailer', 'List ID')],
            'elementId' => ['label' => Craft::t('sprout-module-mailer', 'Element ID')],
            'view' => ['label' => Craft::t('sprout-module-mailer', 'View Subscribers')],
            'count' => ['label' => Craft::t('sprout-module-mailer', 'Count')],
            'dateCreated' => ['label' => Craft::t('sprout-module-mailer', 'Date Created')],
            'manage' => ['label' => Craft::t('sprout-module-mailer', 'Manage')],
        ];
    }

    protected static function defineActions(string $source = null): array
    {
        $actions = parent::defineActions($source);

        $actions[] = Delete::class;

        return $actions;
    }

    public function getAudience(): AudienceType
    {
        $audience = new $this->audienceType();

        if ($this->audienceSettings) {
            $audience->setAttributes($this->audienceSettings, false);
        }

        return $audience;
    }

    /**
     * Use the name as the string representation.
     *
     * @noinspection PhpInconsistentReturnPointsInspection
     */
    public function __toString(): string
    {
        try {
            return $this->name;
        } catch (Throwable $throwable) {
            ErrorHandler::convertExceptionToError($throwable);
        }
    }

    public function getSidebarHtml(bool $static): string
    {
        $groups = self::getSourceGroups();

        //        $audiences = MailerModule::getInstance()->audiences->getAudiences();

        //        $audienceTypes = TemplateHelper::optionsFromComponentTypes($audiences);

        $html = Craft::$app->getView()->renderTemplate('sprout-module-mailer/audience/_details', [
            'element' => $this,
            'static' => $static,
            'groups' => $groups,
            //            'audienceTypeOptions' => $audienceTypes,
        ]);

        return $html . parent::getSidebarHtml($static);
    }

    public function cpEditUrl(): ?string
    {
        $path = UrlHelper::cpUrl('sprout/email/audiences/edit/' . $this->id);

        $params = [];

        if (Craft::$app->getIsMultiSite()) {
            $params['site'] = $this->getSite()->handle;
        }

        return UrlHelper::cpUrl($path, $params);
    }

    public function getPostEditUrl(): ?string
    {
        return UrlHelper::cpUrl('sprout/email/audience');
    }

    public function prepareEditScreen(Response $response, string $containerId): void
    {
        $crumbs = [
            [
                'label' => Craft::t('sprout-module-mailer', 'Email'),
                'url' => UrlHelper::url('sprout/email'),
            ],
            [
                'label' => Craft::t('sprout-module-mailer', 'Audience'),
                'url' => UrlHelper::url('sprout/email/audience'),
            ],
        ];

        /** @var Response|CpScreenResponseBehavior $response */
        $response->crumbs($crumbs);
    }

    public function hasItem(array $criteria): bool
    {
        // Always use the List ID of the current list
        $criteria['listId'] = $this->id;

        /** @var Subscription $subscription */
        $subscription = MailerModule::getInstance()->subscriberLists->populateSubscriptionFromCriteria($criteria);
        $subscriberOrItem = SubscriberHelper::getSubscriberOrItem($subscription);

        if (!$subscriberOrItem) {
            return false;
        }

        return (new Query())
            ->select(['id'])
            ->from([SproutTable::SUBSCRIPTIONS])
            ->where([
                'listId' => $this->id,
                'itemId' => $subscriberOrItem->getId(),
            ])
            ->exists();
    }

    public function isSubscribed(array $criteria): bool
    {
        return $this->hasItem($criteria);
    }

    public function getTableAttributeHtml(string $attribute): string
    {
        $count = $this->count;

        switch ($attribute) {
            case 'handle':
                return '<code>' . $this->handle . '</code>';

            case 'view':
                if ($this->id && $count > 0) {
                    return '<a href="' . UrlHelper::cpUrl('sprout/email/subscribers/' . $this->handle) . '" class="go">' .
                        Craft::t('sprout-module-mailer', 'View Subscribers') . '</a>';
                }

                return '';

            case 'manage':
                return '<a href="' . UrlHelper::cpUrl('sprout/email/subscribers/' . $this->handle) . '" class="go">' .
                    Craft::t('sprout-module-mailer', 'Subscribers') . '</a>';
        }

        return parent::getTableAttributeHtml($attribute);
    }

    public function getFieldLayout(): ?FieldLayout
    {
        return Craft::$app->getFields()->getLayoutByType(static::class);
    }

    public function afterSave(bool $isNew): void
    {
        // Get the list record
        if (!$isNew) {
            $record = AudienceElementRecord::findOne($this->id);

            if (!$record instanceof AudienceElementRecord) {
                throw new ElementNotFoundException('Invalid list ID: ' . $this->id);
            }
            //            $record->elementId = $this->elementId;
        } else {
            $record = new AudienceElementRecord();
            $record->id = $this->id;

            // Fallback and assign the current listId if no elementId is provided
            $record->elementId = $this->elementId ?? $this->id;
        }

        $record->groupId = $this->groupId;
        $record->name = $this->name;
        $record->handle = $this->handle;
        $record->audienceType = $this->audienceType;
        $record->audienceSettings = Json::encode($this->audienceSettings);
        $record->count = $this->count;

        $record->save(false);

        // Update the entry's descendants, who may be using this entry's URI in their own URIs
        Craft::$app->getElements()->updateElementSlugAndUri($this);

        parent::afterSave($isNew);
    }

    public function canView(User $user): bool
    {
        return $user->can('sprout-module-mailer:editLists');
    }

    public function canSave(User $user): bool
    {
        return $user->can('sprout-module-mailer:editLists');
    }

    public function canDelete(User $user): bool
    {
        return $user->can('sprout-module-mailer:editLists');
    }

    public function canDuplicate(User $user): bool
    {
        return false;
    }

    protected function metadata(): array
    {
        return [
            Craft::t('sprout-module-mailer', 'Audience Type') => $this->audienceType::displayName(),
        ];
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['name'], 'required', 'except' => self::SCENARIO_ESSENTIALS];
        $rules[] = [['handle'], 'required', 'except' => self::SCENARIO_ESSENTIALS];
        $rules[] = [['groupId'], 'safe'];
        $rules[] = [['audienceType'], 'safe'];
        $rules[] = [['audienceSettings'], 'safe'];

        $rules[] = [
            ['handle'],
            SlugValidator::class,
            'except' => self::SCENARIO_ESSENTIALS,
        ];
        $rules[] = [
            ['elementId', 'handle'],
            UniqueValidator::class,
            'targetClass' => AudienceElementRecord::class,
            'targetAttribute' => ['elementId', 'handle'],
        ];

        return $rules;
    }
}
