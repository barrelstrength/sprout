<?php

namespace BarrelStrength\Sprout\mailer\components\elements\email;

use BarrelStrength\Sprout\core\twig\TemplateHelper;
use BarrelStrength\Sprout\mailer\components\elements\email\conditions\EmailCondition;
use BarrelStrength\Sprout\mailer\components\elements\email\fieldlayoutelements\PreheaderTextField;
use BarrelStrength\Sprout\mailer\components\elements\email\fieldlayoutelements\SubjectLineField;
use BarrelStrength\Sprout\mailer\email\EmailType;
use BarrelStrength\Sprout\mailer\emailthemes\EmailTheme;
use BarrelStrength\Sprout\mailer\emailthemes\EmailThemeRecord;
use BarrelStrength\Sprout\mailer\MailerModule;
use BarrelStrength\Sprout\mailer\mailers\Mailer;
use BarrelStrength\Sprout\mailer\mailers\MailerInstructionsInterface;
use BarrelStrength\Sprout\mailer\mailers\MailerSendTestInterface;
use Craft;
use craft\base\Element;
use craft\elements\actions\Delete;
use craft\elements\actions\SetStatus;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\db\ElementQueryInterface;
use craft\elements\User;
use craft\fieldlayoutelements\HorizontalRule;
use craft\fieldlayoutelements\TextField;
use craft\fieldlayoutelements\TitleField;
use craft\helpers\Html;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\web\CpScreenResponseBehavior;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Validation\RFCValidation;
use http\Exception\InvalidArgumentException;
use yii\web\Response;

/**
 *
 * @property null|EmailType $emailType
 * @property null|Mailer $mailer
 */
class EmailElement extends Element implements EmailPreviewInterface
{
    use EmailPreviewTrait;

    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    // Package Statuses

    public const ENABLED = 'enabled';

    public const PENDING = 'pending';

    // Send Workflow

    public const DISABLED = 'disabled';

    //-------  SUBJECT --------

    /**
     * The Subject Line of your email. Your title will also default to the Subject Line unless you set a Title Format.
     */
    public string $subjectLine = '';

    public string $preheaderText = '';

    public string $defaultBody = '';

    public ?int $emailThemeId = null;

    public ?string $emailType = null;

    public array $emailTypeSettings = [];

    public ?int $mailerId = null;

    public array $mailerInstructionsSettings = [];

    private ?FieldLayout $_fieldLayout = null;

    private ?Mailer $_mailer = null;

    private ?MailerInstructionsInterface $_mailerInstructionsSettingsModel = null;

    private ?MailerInstructionsInterface $_mailerInstructionsTestSettingsModel = null;

    private ?EmailType $_emailTypeSettingsModel = null;

    public static function displayName(): string
    {
        return Craft::t('sprout-module-mailer', 'Email');
    }

    public static function lowerDisplayName(): string
    {
        return Craft::t('sprout-module-mailer', 'email');
    }

    public static function pluralDisplayName(): string
    {
        return Craft::t('sprout-module-mailer', 'Emails');
    }

    public static function pluralLowerDisplayName(): string
    {
        return Craft::t('sprout-module-mailer', 'emails');
    }

    public static function refHandle(): ?string
    {
        return 'email';
    }

    public static function hasContent(): bool
    {
        return true;
    }

    public static function hasTitles(): bool
    {
        return true;
    }

    public static function hasUris(): bool
    {
        return true;
    }

    public static function hasStatuses(): bool
    {
        return true;
    }

    public static function find(): ElementQueryInterface
    {
        return new EmailElementQuery(static::class);
    }

    public static function createCondition(): ElementConditionInterface
    {
        return Craft::createObject(EmailCondition::class, [static::class]);
    }

    public static function trackChanges(): bool
    {
        return true;
    }

    protected static function defineTableAttributes(): array
    {
        return [
            'subjectLine' => ['label' => Craft::t('sprout-module-mailer', 'Subject Line')],
            'dateCreated' => ['label' => Craft::t('sprout-module-mailer', 'Date Created')],
            'sendTest' => ['label' => Craft::t('sprout-module-mailer', 'Send Test')],
            'preview' => ['label' => Craft::t('sprout-module-mailer', 'Preview'), 'icon' => 'view'],
        ];
    }

    //    public static function isLocalized(): bool
    //    {
    //        return true;
    //    }

    protected static function defineSortOptions(): array
    {
        return [
            'title' => Craft::t('sprout-module-mailer', 'Name', [
                'emailType' => static::displayName(),
            ]),
            'subjectLine' => Craft::t('sprout-module-mailer', 'Subject Line'),
            [
                'label' => Craft::t('sprout-module-mailer', 'Date Created'),
                'orderBy' => 'elements.dateCreated',
                'attribute' => 'dateCreated',
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

    protected static function defineActions(string $source = null): array
    {
        $actions = parent::defineActions($source);

        $actions[] = SetStatus::class;
        $actions[] = Delete::class;

        return $actions;
    }

    protected static function defineSources(string $context = null): array
    {
        return [
            [
                'key' => '*',
                'label' => Craft::t('sprout-module-mailer', 'All campaigns'),
            ],
        ];
    }

    public function prepareEditScreen(Response $response, string $containerId): void
    {
        $crumbs[] = [
            'label' => Craft::t('sprout-module-mailer', 'Email'),
            'url' => UrlHelper::url('sprout/email'),
        ];

        $emailType = $this->getEmailTypeSettings();

        $crumbs[] = [
            'label' => $emailType::displayName(),
            'url' => UrlHelper::url('sprout/email/' . $emailType->handle),
        ];

        /** @var Response|CpScreenResponseBehavior $response */
        $response->crumbs($crumbs);
    }

    /**
     * The Email Service provide can be update via Craft's Email Settings
     */
    public function getMailer(): Mailer
    {
        if ($this->_mailer) {
            return $this->_mailer;
        }

        $emailTypeSettings = $this->getEmailTypeSettings();

        return $emailTypeSettings->getMailer();
    }

    public function setMailer(?Mailer $mailer): void
    {
        $this->_mailer = $mailer;
    }

    public function getMailerInstructionsSettings(): ?MailerInstructionsInterface
    {
        if ($this->_mailerInstructionsSettingsModel !== null) {
            return $this->_mailerInstructionsSettingsModel;
        }

        $mailer = $this->getMailer();
        $model = $mailer->createMailerInstructionsSettingsModel();
        $model->setAttributes($this->mailerInstructionsSettings, false);

        $this->_mailerInstructionsSettingsModel = $model;

        return $this->_mailerInstructionsSettingsModel;
    }

    public function getEmailTypeSettings(): EmailType
    {
        if ($this->_emailTypeSettingsModel !== null) {
            return $this->_emailTypeSettingsModel;
        }

        $emailType = new $this->emailType();
        $emailType->setAttributes($this->emailTypeSettings, false);

        $this->_emailTypeSettingsModel = $emailType;

        return $this->_emailTypeSettingsModel;
    }

    /**
     * Returns the Email Type contract for this email
     *
     * EMAIL TYPE determines:
     * $fieldLayoutId
     * $emailThemeId
     *
     * @return EmailThemeRecord|null
     */
    public function getEmailTheme(): ?EmailTheme
    {
        $emailTheme = MailerModule::getInstance()->emailThemes->getEmailThemeById($this->emailThemeId);
        $emailTheme->email = $this;

        return $emailTheme;
    }

    /**
     * Send an email
     *
     * For sending programmatic emails or test emails, implement the mailer directly
     * and provide an [[EmailInstructionsInterface]] configured for your test
     *
     * $email = new EmailElement();
     * ...
     * $mailer = $email->getMailer();
     * $mailer->send($this, $mailerInstructionsTestSettings);
     */
    public function send(): void
    {
        $mailer = $this->getMailer();

        $mailer->send($this, $this->getMailerInstructionsSettings());
    }

    public function getAdditionalButtons(): string
    {
        $emailType = $this->getEmailTypeSettings();

        return $emailType::getAdditionalButtonsHtml($this) . parent::getAdditionalButtons();
    }

    public function getTableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {

            case 'sendTest':

                if (!$this->getMailer() instanceof MailerSendTestInterface) {
                    return '&mdash;';
                }

                return Html::tag('a', Craft::t('sprout-module-mailer', 'Send Test'), [
                    'href' => '#',
                    'class' => 'sprout-send-email-btn btn small formsubmit',
                    'data-email-id' => $this->id,
                    'data-get-send-email-html-action' => 'sprout-module-mailer/mailer/get-send-test-html',
                    'data-send-email-action' => 'sprout-module-mailer/mailer/send-test',
                    'data-modal-title' => Craft::t('sprout-module-mailer', 'Send a test'),
                    'data-modal-action-btn-label' => Craft::t('sprout-module-mailer', 'Send Test Now'),
                ]);

            case 'preview':

                return Html::tag('a', Craft::t('sprout-module-mailer', ''), [
                    'href' => $this->getPreviewUrl(),
                    'rel' => 'noopener',
                    'target' => '_blank',
                    'class' => 'email-preview',
                    'data-icon' => 'view',
                    'data-element-type' => self::class,
                ]);
        }

        return parent::getTableAttributeHtml($attribute);
    }

    public function getPreviewType(): string
    {
        return self::EMAIL_TEMPLATE_TYPE_DYNAMIC;
    }

    public function cpEditUrl(): ?string
    {
        $emailTypeSlug = $this->getEmailTypeSettings()->handle;

        return 'sprout/email/' . $emailTypeSlug . '/edit/' . $this->getCanonicalId();
    }

    public function getPostEditUrl(): ?string
    {
        $emailTypeSlug = $this->getEmailTypeSettings()->handle;

        return UrlHelper::cpUrl('sprout/email/' . $emailTypeSlug);
    }

    public function getFieldLayout(): ?FieldLayout
    {
        if ($this->_fieldLayout) {
            return $this->_fieldLayout;
        }

        $emailTheme = $this->getEmailTheme();

        if ($emailTheme->fieldLayoutId) {
            $this->_fieldLayout = $emailTheme->getFieldLayout();
        } else {
            $this->_fieldLayout = new FieldLayout([
                'type' => self::class,
            ]);
        }

        $emailType = $this->getEmailTypeSettings();
        $mailer = $this->getMailer();

        $subjectTab = new FieldLayoutTab();
        $subjectTab->layout = $this->_fieldLayout;
        $subjectTab->name = Craft::t('sprout-module-mailer', 'Subject');
        $subjectTab->sortOrder = -1;
        $subjectTab->uid = 'SPROUT-UID-EMAIL-PREPARE-TAB';
        $subjectTab->setElements([
            new TitleField([
                'label' => Craft::t('sprout-module-mailer', 'Email Name'),
            ]),
            new HorizontalRule(),
            new SubjectLineField(),
            new PreheaderTextField(),
            new TextField([
                'type' => 'hidden',
                'name' => 'mailerId',
                'attribute' => 'mailerId',
                'containerAttributes' => [
                    'class' => 'hidden',
                ],
            ]),
            new TextField([
                'type' => 'hidden',
                'name' => 'emailType',
                'attribute' => 'emailType',
                'containerAttributes' => [
                    'class' => 'hidden',
                ],
            ]),
        ]);

        $newTabs = array_merge(
            [$subjectTab],
            $mailer::getTabs($this->getFieldLayout()),
            $emailType::getTabs($this->getFieldLayout()),
            $this->_fieldLayout->getTabs()
        );

        $this->_fieldLayout->setTabs($newTabs);

        return $this->_fieldLayout;
    }

    public function getSidebarHtml(bool $static): string
    {
        $mailers = MailerModule::getInstance()->mailers->getRegisteredMailers();
        $mailerTypeOptions = TemplateHelper::optionsFromComponentTypes($mailers);

        $themes = MailerModule::getInstance()->emailThemes->getEmailThemes();

        $templateOptions = [];
        foreach ($themes as $theme) {
            $templateOptions[] = [
                'label' => $theme->name,
                'value' => $theme->id,
            ];
        }

        $meta = Craft::$app->getView()->renderTemplate('sprout-module-mailer/email/_meta', [
            'element' => $this,
            'mailer' => $this->getMailer(),
            'templateOptions' => $templateOptions,
            'mailerTypeOptions' => $mailerTypeOptions,
        ]);

        return $meta . parent::getSidebarHtml($static);
    }

    public function afterSave(bool $isNew): void
    {

        if (!$isNew) {
            $emailElementRecord = EmailElementRecord::findOne($this->id);

            if (!$emailElementRecord instanceof EmailElementRecord) {
                throw new InvalidArgumentException('Invalid email ID: ' . $this->id);
            }
        } else {
            $emailElementRecord = new EmailElementRecord();
            $emailElementRecord->id = $this->id;
        }

        $emailElementRecord->subjectLine = $this->subjectLine;
        $emailElementRecord->preheaderText = $this->preheaderText;
        $emailElementRecord->defaultBody = $this->defaultBody;

        $emailElementRecord->emailThemeId = $this->emailThemeId;

        $emailElementRecord->mailerId = $this->mailerId;
        $emailElementRecord->mailerInstructionsSettings = $this->mailerInstructionsSettings;

        $emailElementRecord->emailType = $this->emailType;
        $emailElementRecord->emailTypeSettings = $this->emailTypeSettings;

        $emailElementRecord->dateCreated = $this->dateCreated;
        $emailElementRecord->dateUpdated = $this->dateUpdated;

        $emailElementRecord->save(false);

        parent::afterSave($isNew);
    }

    public function canView(User $user): bool
    {
        return $user->can('sprout-module-mailer:editEmail');
    }

    public function canSave(User $user): bool
    {
        return $user->can('sprout-module-mailer:editEmail');
    }

    public function canDelete(User $user): bool
    {
        return $user->can('sprout-module-mailer:editEmail');
    }

    public function canDuplicate(User $user): bool
    {
        return $user->can('sprout-module-mailer:editEmail');
    }

    public function canCreateDrafts(User $user): bool
    {
        return true;
    }

    public function validateEmailList($attribute): bool
    {
        $recipients = $this->{$attribute};
        $validator = new EmailValidator();
        $multipleValidations = new MultipleValidationWithAnd([
            new RFCValidation(),
        ]);

        // Add any On The Fly Recipients to our List
        if (!empty($recipients)) {
            $recipientArray = explode(',', trim($recipients));

            foreach ($recipientArray as $recipient) {
                // Let the user use shorthand syntax and don't validate it
                if (str_contains($recipient, '{')) {
                    continue;
                }

                // Validate actual emails
                if (!$validator->isValid(trim($recipient), $multipleValidations)) {

                    $this->addError($attribute, Craft::t('sprout-module-mailer',
                        'Email is invalid: ' . $recipient));
                }
            }
        }

        return true;
    }

    /**
     * Confirm that an email is enabled.
     */
    public function isReady(): bool
    {
        return $this->getStatus() === static::ENABLED;
    }

    protected function statusFieldHtml(): string
    {
        if ($this->getEmailTypeSettings()->canBeDisabled()) {
            return parent::statusFieldHtml();
        }

        return '';
    }

    protected function previewTargets(): array
    {
        $previewTargets[] = [
            'label' => Craft::t('app', 'Primary {type} page', [
                'type' => self::lowerDisplayName(),
            ]),
            'url' => $this->getPreviewUrl(),
        ];

        return $previewTargets;
    }

    protected function route(): array|string|null
    {
        return [
            'templates/render', [
                'template' => $this->getEmailTheme()->htmlEmailTemplatePath,
                'variables' => [
                    'email' => $this,
                ],
            ],
        ];
    }

    protected function metadata(): array
    {
        return [
            Craft::t('sprout-module-mailer', 'Package Type') => $this->getEmailTypeSettings()::displayName(),
        ];
    }

    //    protected static function defineFieldLayouts(string $source): array
    //    {
    //        // Get all the sections covered by this source
    //        $sections = [];
    //        if ($source === '*') {
    //            $sections = Craft::$app->getSections()->getAllSections();
    //        } elseif ($source === 'singles') {
    //            $sections = Craft::$app->getSections()->getSectionsByType(Section::TYPE_SINGLE);
    //        } elseif (
    //            preg_match('/^section:(.+)$/', $source, $matches) &&
    //            $section = Craft::$app->getSections()->getSectionByUid($matches[1])
    //        ) {
    //            $sections = [$section];
    //        }
    //
    //        $fieldLayouts = [];
    //        foreach ($sections as $section) {
    //            foreach ($section->getEntryTypes() as $entryType) {
    //                $fieldLayouts[] = $entryType->getFieldLayout();
    //            }
    //        }
    //        return $fieldLayouts;
    //    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['title', 'subjectLine'], 'required', 'except' => self::SCENARIO_ESSENTIALS];
        $rules[] = [['preheaderText'], 'safe'];
        $rules[] = [['defaultBody'], 'safe'];

        $rules[] = [['emailThemeId'], 'safe'];

        $rules[] = [['emailType'], 'safe'];
        $rules[] = [['emailTypeSettings'], 'safe'];
        $rules[] = [['mailerId'], 'safe'];
        $rules[] = [['mailerInstructionsSettings'], 'safe'];

        return $rules;
    }
}
