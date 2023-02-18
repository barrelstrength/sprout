<?php

namespace BarrelStrength\Sprout\redirects\components\elements;

use BarrelStrength\Sprout\redirects\components\elements\actions\ChangePermanentStatusCode;
use BarrelStrength\Sprout\redirects\components\elements\actions\ChangeTemporaryStatusCode;
use BarrelStrength\Sprout\redirects\components\elements\actions\ExcludeUrl;
use BarrelStrength\Sprout\redirects\components\elements\db\RedirectElementQuery;
use BarrelStrength\Sprout\redirects\components\elements\fieldlayoutelements\MatchStrategyField;
use BarrelStrength\Sprout\redirects\components\elements\fieldlayoutelements\NewUrlField;
use BarrelStrength\Sprout\redirects\components\elements\fieldlayoutelements\OldUrlField;
use BarrelStrength\Sprout\redirects\components\elements\fieldlayoutelements\StatusCodeField;
use BarrelStrength\Sprout\redirects\editions\EditionHelper;
use BarrelStrength\Sprout\redirects\redirects\MatchStrategy;
use BarrelStrength\Sprout\redirects\redirects\PageNotFoundHelper;
use BarrelStrength\Sprout\redirects\redirects\RedirectHelper;
use BarrelStrength\Sprout\redirects\redirects\RedirectsRecord;
use BarrelStrength\Sprout\redirects\redirects\StatusCode;
use BarrelStrength\Sprout\redirects\RedirectsModule;
use Craft;
use craft\base\Element;
use craft\elements\actions\Delete;
use craft\elements\actions\Duplicate;
use craft\elements\actions\Edit;
use craft\elements\actions\SetStatus;
use craft\elements\db\ElementQueryInterface;
use craft\elements\User;
use craft\events\DefineFieldLayoutFieldsEvent;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\web\CpScreenResponseBehavior;
use DateTime;
use yii\base\Exception;
use yii\helpers\Markdown;
use yii\web\Response;

class RedirectElement extends Element
{
    public ?string $oldUrl = null;

    public ?string $newUrl = null;

    public ?int $statusCode = null;

    public string $matchStrategy = MatchStrategy::EXACT_MATCH;

    public int $count = 0;

    public ?string $lastRemoteIpAddress = null;

    public ?string $lastReferrer = null;

    public ?string $lastUserAgent = null;

    public ?DateTime $dateLastUsed = null;

    public static function displayName(): string
    {
        if ($displayName = Craft::$app->getSession()->get('sprout-redirect-element-index-display-name')) {
            Craft::$app->getSession()->remove('sprout-redirect-element-index-display-name');

            return $displayName;
        }

        return Craft::t('sprout-module-redirects', 'Redirect');
    }

    public static function lowerDisplayName(): string
    {
        return Craft::t('sprout-module-redirects', 'redirect');
    }

    public static function pluralDisplayName(): string
    {
        return Craft::t('sprout-module-redirects', 'Redirects');
    }

    public static function pluralLowerDisplayName(): string
    {
        return Craft::t('sprout-module-redirects', 'redirects');
    }

    public static function hasContent(): bool
    {
        return true;
    }

    public static function hasStatuses(): bool
    {
        return true;
    }

    public static function isLocalized(): bool
    {
        return true;
    }

    public static function find(): RedirectElementQuery
    {
        return new RedirectElementQuery(static::class);
    }

    public static function indexHtml(ElementQueryInterface $elementQuery, ?array $disabledElementIds, array $viewState, ?string $sourceKey, ?string $context, bool $includeContainer, bool $showCheckboxes): string
    {
        Craft::$app->getSession()->set('sprout-redirect-element-index-display-name', 'Old URL');

        return parent::indexHtml($elementQuery, $disabledElementIds, $viewState, $sourceKey, $context, $includeContainer, $showCheckboxes);
    }

    public static function defineNativeFields(DefineFieldLayoutFieldsEvent $event): void
    {
        /** @var FieldLayout $fieldLayout */
        $fieldLayout = $event->sender;

        if ($fieldLayout->type === self::class) {
            $event->fields[] = OldUrlField::class;
            $event->fields[] = NewUrlField::class;
            $event->fields[] = StatusCodeField::class;
            $event->fields[] = MatchStrategyField::class;
        }
    }

    protected static function defineTableAttributes(): array
    {
        return [
            'newUrl' => Craft::t('sprout-module-redirects', 'New URL'),
            'statusCode' => Craft::t('sprout-module-redirects', 'Status Code'),
            'count' => Craft::t('sprout-module-redirects', 'Count'),
            'dateLastUsed' => Craft::t('sprout-module-redirects', 'Date Last Used'),
            'test' => Craft::t('sprout-module-redirects', 'Test'),
            'lastRemoteIpAddress' => Craft::t('sprout-module-redirects', 'Last Remote IP'),
            'lastReferrer' => Craft::t('sprout-module-redirects', 'Last Referrer'),
            'lastUserAgent' => Craft::t('sprout-module-redirects', 'Last User Agent'),
        ];
    }

    protected static function defineSearchableAttributes(): array
    {
        return [
            'oldUrl',
            'newUrl',
            'statusCode',
            'matchStrategy',
        ];
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        return [
            'newUrl',
            'statusCode',
            'count',
            'dateLastUsed',
            'test',
        ];
    }

    protected static function defineSortOptions(): array
    {
        return [
            'oldUrl' => Craft::t('sprout-module-redirects', 'Old URL'),
            'newUrl' => Craft::t('sprout-module-redirects', 'New URL'),
            'statusCode' => Craft::t('sprout-module-redirects', 'Status Code'),
            [
                'label' => Craft::t('sprout-module-redirects', 'Count'),
                'orderBy' => 'sprout_redirects.count',
                'attribute' => 'count',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('sprout-module-redirects', 'Date Last Used'),
                'orderBy' => 'dateLastUsed',
                'attribute' => 'dateLastUsed',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('sprout-module-redirects', 'Date Created'),
                'orderBy' => 'elements.dateCreated',
                'attribute' => 'dateCreated',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('sprout-module-redirects', 'Date Updated'),
                'orderBy' => 'elements.dateUpdated',
                'attribute' => 'dateUpdated',
                'defaultDir' => 'desc',
            ],
        ];
    }

    protected static function defineSources(string $context = null): array
    {
        $settings = RedirectsModule::getInstance()->getSettings();

        $sources = [
            [
                'key' => '*',
                'label' => Craft::t('sprout-module-redirects', 'All redirects'),
                'structureId' => RedirectHelper::getStructureId(),
                'structureEditable' => true,
                'criteria' => [
                    'statusCode' => [
                        StatusCode::PERMANENT,
                        StatusCode::TEMPORARY,
                    ],
                ],
                'defaultSort' => ['structure'],
            ],
        ];

        $sources[] = [
            'heading' => Craft::t('sprout-module-redirects', 'Status Codes'),
        ];

        $statusCodes = RedirectHelper::getStatusCodes();

        foreach ($statusCodes as $code => $statusCode) {

            $key = 'statusCode:' . $code;

            // Hide 404 Redirect tab if disabled
            if (empty($settings->enable404RedirectLog) && $code === StatusCode::PAGE_NOT_FOUND) {
                continue;
            }

            $sources[] = [
                'key' => $key,
                'label' => $statusCode,
                'criteria' => [
                    'statusCode' => [
                        $code,
                    ],
                ],
                'structureEditable' => true,
                'defaultSort' => ['count', 'desc'],
            ];
        }

        return $sources;
    }

    protected static function defineActions(string $source = null): array
    {
        $actions = parent::defineActions($source);

        $currentUser = Craft::$app->getUser()->getIdentity();
        $canEditRedirects = $currentUser->can(RedirectsModule::p('editRedirects'));

        if (!$canEditRedirects) {
            return $actions;
        }

        if ($source !== 'statusCode:' . StatusCode::PAGE_NOT_FOUND) {
            $actions[] = SetStatus::class;
        }

        // Edit
        $actions[] = Craft::$app->getElements()->createAction([
            'type' => Edit::class,
            'label' => Craft::t('sprout-module-redirects', 'Edit Redirect'),
        ]);

        $actions[] = Duplicate::class;

        // Change Permanent Status Code
        $actions[] = Craft::$app->getElements()->createAction([
            'type' => ChangePermanentStatusCode::class,
            'source' => $source,
        ]);

        // Change Temporary Status Code
        $actions[] = Craft::$app->getElements()->createAction([
            'type' => ChangeTemporaryStatusCode::class,
            'source' => $source,
        ]);

        // Delete
        if ($source === 'statusCode:' . StatusCode::PAGE_NOT_FOUND) {
            $actions[] = Craft::$app->getElements()->createAction([
                'type' => ExcludeUrl::class,
            ]);
        }

        $actions[] = Craft::$app->getElements()->createAction([
            'type' => Delete::class,
            'hard' => true,
        ]);

        return $actions;
    }

    public function getSupportedSites(): array
    {
        // limit to just the one site this element is set to so that we don't propagate when saving
        return [$this->siteId];
    }

    public function __toString(): string
    {
        if ($this->oldUrl) {
            return $this->oldUrl;
        }

        return parent::__toString();
    }

    public function getFieldLayout(): ?FieldLayout
    {
        $settings = RedirectsModule::getInstance()->getSettings();

        return $settings->getFieldLayout();
    }

    public function getPostEditUrl(): ?string
    {
        return UrlHelper::cpUrl('sprout/redirects');
    }

    public function getAdditionalButtons(): string
    {
        $html = Craft::$app->getView()->renderTemplate('sprout-module-core/_components/upgrade/button', [
            'module' => RedirectsModule::getInstance(),
        ]);

        return $html . parent::getAdditionalButtons();
    }

    public function prepareEditScreen(Response $response, string $containerId): void
    {
        $crumbs = [
            [
                'label' => Craft::t('sprout-module-redirects', 'Redirects'),
                'url' => UrlHelper::url('sprout/redirects'),
            ],
        ];

        /** @var Response|CpScreenResponseBehavior $response */
        $response->crumbs($crumbs);
    }

    public function beforeSave(bool $isNew): bool
    {
        // Set the structure ID for Element::attributes() and afterSave()
        $this->structureId = RedirectHelper::getStructureId();

        if ($this->duplicateOf instanceof self) {
            $this->oldUrl .= '-1';
            $this->count = 0;
            $this->enabled = false;
        }

        PageNotFoundHelper::remove404RedirectIfExists($this);

        return parent::beforeSave($isNew);
    }

    /**
     * Update "oldUrl" and "newUrl" to starts with a "/"
     */
    public function beforeValidate(): bool
    {
        if ($this->oldUrl && !$this->matchStrategy) {
            $this->oldUrl = RedirectHelper::removeSlash($this->oldUrl);
        }

        if ($this->newUrl) {
            $this->newUrl = RedirectHelper::removeSlash($this->newUrl);

            // In case the value was a backslash: /
            if (empty($this->newUrl)) {
                $this->newUrl = null;
            }
        } else {
            $this->newUrl = null;
        }

        return parent::beforeValidate();
    }

    public function afterSave(bool $isNew): void
    {
        $settings = RedirectsModule::getInstance()->getSettings();

        // Get the Redirect record
        if (!$isNew) {
            $record = RedirectsRecord::findOne($this->id);

            if (!$record instanceof RedirectsRecord) {
                throw new Exception('Invalid Redirect ID: ' . $this->id);
            }
        } else {
            $record = new RedirectsRecord();
            $record->id = $this->id;
        }

        $record->oldUrl = RedirectHelper::removeSlash($this->oldUrl);
        $record->newUrl = RedirectHelper::removeSlash($this->newUrl);
        $record->statusCode = $this->statusCode;
        $record->matchStrategy = $this->matchStrategy;
        $record->count = $this->count;
        $record->lastRemoteIpAddress = $settings->trackRemoteIp ? $this->lastRemoteIpAddress : null;
        $record->lastReferrer = $this->lastReferrer;
        $record->lastUserAgent = $this->lastUserAgent;
        $record->dateLastUsed = $this->dateLastUsed;

        $record->save(false);

        $structureId = RedirectHelper::getStructureId();

        if ($isNew) {
            Craft::$app->structures->appendToRoot($structureId, $this);
        }

        parent::afterSave($isNew);
    }

    public function getAbsoluteNewUrl(): string
    {
        $baseUrl = Craft::getAlias($this->getSite()->getBaseUrl());

        return $baseUrl . $this->newUrl;
    }

    /**
     * Add validation to unique oldUrls
     */
    public function uniqueUrl($attribute): void
    {
        $redirect = self::find()
            ->siteId($this->siteId)
            ->where(['like binary', 'oldUrl', $this->$attribute])
            ->andWhere([
                'in', 'statusCode', [
                    StatusCode::TEMPORARY,
                    StatusCode::PERMANENT,
                ],
            ])
            ->one();

        if ($redirect && $redirect->id != $this->id) {
            $this->addError($attribute, Craft::t('sprout-module-redirects', 'This url already exists.'));
        }
    }

    public function hasTrailingSlashIfAbsolute($attribute): void
    {
        if (!UrlHelper::isAbsoluteUrl($this->{$attribute})) {
            return;
        }

        $newUrl = parse_url($this->{$attribute});

        if (isset($newUrl['host']) && str_contains($newUrl['host'], '$')) {
            $this->addError($attribute, Craft::t('sprout-module-redirects', 'The host name ({host}) of an absolute URL cannot contain capture groups.', [
                'host' => $newUrl['host'] ?? null,
            ]));
        }

        // I don't believe we'll hit this condition but just in case
        if (!isset($newUrl['path']) || (!str_starts_with($newUrl['path'], RedirectHelper::SLASH_CHARACTER))) {
            $this->addError($attribute, Craft::t('sprout-module-redirects', 'The host name ({host}) of an absolute URL must end with a slash.', [
                'host' => $newUrl['host'] ?? null,
            ]));
        }
    }

    public function canView(User $user): bool
    {
        return $user->can(RedirectsModule::p('editRedirects'));
    }

    public function canSave(User $user): bool
    {
        return $user->can(RedirectsModule::p('editRedirects'));
    }

    public function canDelete(User $user): bool
    {
        return $user->can(RedirectsModule::p('editRedirects'));
    }

    public function canDuplicate(User $user): bool
    {
        return $user->can(RedirectsModule::p('editRedirects'));
    }

    public function canCreateDrafts(User $user): bool
    {
        return false;
    }

    protected function statusFieldHtml(): string
    {
        $markdown = Craft::$app->getView()->renderTemplate('sprout-module-redirects/_redirects/regex-info.md');

        $html = Html::beginTag('fieldset') .
            Html::tag('div', Markdown::process($markdown), ['class' => 'meta sprout-sidebar-info']) .
            Html::endTag('fieldset');

        $statusField = Cp::lightswitchFieldHtml([
            'id' => 'enabled',
            'label' => Craft::t('app', 'Enabled'),
            'name' => 'enabled',
            'on' => $this->enabled,
            'disabled' => $this->getIsRevision(),
            'status' => $this->getAttributeStatus('enabled'),
        ]);

        $statusHtml = Html::tag('div', $statusField, ['class' => 'meta']);

        return $statusHtml . $html;
    }

    protected function cpEditUrl(): string
    {
        $path = UrlHelper::cpUrl('sprout/redirects/edit/' . $this->id);

        $params = [];

        if (Craft::$app->getIsMultiSite()) {
            $params['site'] = $this->getSite()->handle;
        }

        return UrlHelper::cpUrl($path, $params);
    }

    protected function tableAttributeHtml(string $attribute): string
    {
        if ($attribute == 'dateLastUsed') {
            if ($this->dateLastUsed) {
                return $this->dateLastUsed->format('Y-m-d');
            }

            return '';
        }

        if ($attribute == 'test') {
            // no link for regex
            if ($this->matchStrategy === MatchStrategy::REGEX_MATCH) {
                return ' - ';
            }

            // Send link for testing
            $site = Craft::$app->getSites()->getSiteById($this->siteId);
            if ($site === null) {
                return ' - ';
            }

            $baseUrl = Craft::getAlias($site->getBaseUrl());
            $oldUrl = $baseUrl . $this->oldUrl;
            $text = Craft::t('sprout-module-redirects', 'Test');

            return Html::a($text, $oldUrl, [
                'target' => '_blank',
                'class' => 'go',
            ]);
        }

        return parent::tableAttributeHtml($attribute);
    }

    protected function metadata(): array
    {
        $countMessage = Craft::t('sprout-module-redirects',
            'The total number of times a redirect has been used.'
        );
        $count = ($this->count > 0 ?: '-') . '&nbsp;' . Html::tag('span', $countMessage, [
                'class' => 'info',
            ]);

        $baseUrl = Html::input('text', '', $this->getSite()->getBaseUrl(), [
            'class' => 'text fullwidth',
            'style' => 'padding-left:0;padding-top:0;padding-bottom:0;margin-top:-6px;margin-bottom:-6px;border:none;box-shadow:none;',
        ]);

        $siteName = $this->getSite()->getName();

        return [
            Craft::t('sprout-module-redirects', 'Count') => $count,
            Craft::t('sprout-module-redirects', 'Base URL') => $baseUrl,
            Craft::t('sprout-module-redirects', 'Site') => $siteName,
        ];
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['oldUrl'], 'required', 'except' => self::SCENARIO_ESSENTIALS];
        $rules[] = ['oldUrl', 'uniqueUrl', 'except' => self::SCENARIO_ESSENTIALS];
        $rules[] = ['newUrl', 'hasTrailingSlashIfAbsolute'];
        $rules[] = ['statusCode', 'validateStatusCode'];
        $rules[] = [
            ['statusCode'], 'in', 'range' => StatusCode::values(),
        ];
        $rules[] = [
            ['matchStrategy'], 'in', 'range' => MatchStrategy::values(),
        ];

        return $rules;
    }

    /**
     * Add validation so a user can't save a 404 in "enabled" status
     */
    public function validateStatusCode($attribute): void
    {
        if ($this->enabled && $this->$attribute == StatusCode::PAGE_NOT_FOUND) {
            $this->addError($attribute, 'Cannot enable a 404 Redirect. Update Redirect status code.');
        }
    }
}
