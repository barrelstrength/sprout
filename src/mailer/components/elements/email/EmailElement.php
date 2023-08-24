<?php

namespace BarrelStrength\Sprout\mailer\components\elements\email;

use BarrelStrength\Sprout\core\Sprout;
use BarrelStrength\Sprout\mailer\components\elements\email\conditions\EmailThemeConditionRule;
use BarrelStrength\Sprout\mailer\components\elements\email\conditions\MailerConditionRule;
use BarrelStrength\Sprout\mailer\components\elements\email\conditions\PreheaderTextConditionRule;
use BarrelStrength\Sprout\mailer\components\elements\email\fieldlayoutelements\PreheaderTextField;
use BarrelStrength\Sprout\mailer\components\elements\email\fieldlayoutelements\SubjectLineField;
use BarrelStrength\Sprout\mailer\components\mailers\fieldlayoutelements\ToField;
use BarrelStrength\Sprout\mailer\emailthemes\EmailTheme;
use BarrelStrength\Sprout\mailer\emailthemes\EmailThemeHelper;
use BarrelStrength\Sprout\mailer\emailtypes\EmailType;
use BarrelStrength\Sprout\mailer\mailers\Mailer;
use BarrelStrength\Sprout\mailer\mailers\MailerHelper;
use BarrelStrength\Sprout\mailer\mailers\MailerInstructionsInterface;
use BarrelStrength\Sprout\mailer\mailers\MailerSendTestInterface;
use Craft;
use craft\base\Element;
use craft\elements\actions\Delete;
use craft\elements\actions\SetStatus;
use craft\elements\db\ElementQueryInterface;
use craft\elements\User;
use craft\errors\MissingComponentException;
use craft\fieldlayoutelements\HorizontalRule;
use craft\fieldlayoutelements\TextareaField;
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
 * @property null|EmailType $emailType
 * @property null|Mailer $mailer
 */
class EmailElement extends Element implements EmailPreviewInterface
{
    use EmailPreviewTrait;

    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    // Email Type Statuses

    public const ENABLED = 'enabled';

    public const PENDING = 'pending';

    // Send Workflow

    public const DISABLED = 'disabled';

    //-------  SUBJECT --------

    /**
     * The Email Type of this Email Element
     */
    public ?string $type = null;

    /**
     * The Subject Line of your email. Your title will also default to the Subject Line unless you set a Title Format.
     */
    public string $subjectLine = '';

    public string $preheaderText = '';

    public string $defaultMessage = '';

    public ?string $emailThemeUid = null;

    public array $emailTypeSettings = [];

    public ?string $mailerUid = null;

    public array $mailerInstructionsSettings = [];

    protected ?EmailTheme $_emailTheme = null;

    protected ?EmailType $_emailType = null;

    private ?FieldLayout $_fieldLayout = null;

    private ?Mailer $_mailer = null;

    private ?MailerInstructionsInterface $_mailerInstructionsSettingsModel = null;

    private ?MailerInstructionsInterface $_mailerInstructionsTestSettingsModel = null;

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

    public static function indexHtml(ElementQueryInterface $elementQuery, ?array $disabledElementIds, array $viewState, ?string $sourceKey, ?string $context, bool $includeContainer, bool $showCheckboxes): string
    {
        Sprout::getInstance()->vite->register('mailer/SendEmailModal.js', false);

        return parent::indexHtml($elementQuery, $disabledElementIds, $viewState, $sourceKey, $context, $includeContainer, $showCheckboxes);
    }

    public function prepareEditScreen(Response $response, string $containerId): void
    {
        $crumbs[] = [
            'label' => Craft::t('sprout-module-mailer', 'Email'),
            'url' => UrlHelper::url('sprout/email'),
        ];

        $emailType = $this->getEmailType();

        $crumbs[] = [
            'label' => $emailType::displayName(),
            'url' => UrlHelper::url('sprout/email/' . $emailType::refHandle()),
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

        $emailTypeSettings = $this->getEmailType();

        $mailer = $emailTypeSettings->getMailer($this);

        if (!$mailer) {
            return MailerHelper::getDefaultMailer();
        }

        return $mailer;
    }

    public function setMailer(Mailer $mailer): void
    {
        $this->_mailer = $mailer;
    }

    public function getMailerInstructions(): MailerInstructionsInterface
    {
        if ($this->_mailerInstructionsSettingsModel !== null) {
            return $this->_mailerInstructionsSettingsModel;
        }

        $mailer = $this->getMailer();
        $mailerInstructionsSettings = $mailer->createMailerInstructionsSettingsModel();
        $mailerInstructionsSettings->setAttributes($this->mailerInstructionsSettings, false);

        $this->_mailerInstructionsSettingsModel = $mailerInstructionsSettings;

        return $this->_mailerInstructionsSettingsModel;
    }

    public function setEmailType($emailType): void
    {
        $this->_emailType = $emailType;
    }

    public function getEmailType(): EmailType
    {
        if ($this->_emailType !== null) {
            return $this->_emailType;
        }

        $emailType = new $this->type();
        $emailType->setAttributes($this->emailTypeSettings, false);

        $this->_emailType = $emailType;

        return $this->_emailType;
    }

    /**
     * Returns the Email Type contract for this email
     *
     * EMAIL TYPE determines:
     * $fieldLayout
     * $emailThemeUid
     */
    public function getEmailTheme(): EmailTheme
    {
        if ($this->_emailTheme) {
            return $this->_emailTheme;
        }

        $emailTheme = EmailThemeHelper::getEmailThemeByUid($this->emailThemeUid);

        if (!$emailTheme) {
            $emailTheme = EmailThemeHelper::getDefaultEmailTheme();
        }

        if (!$emailTheme) {
            throw new MissingComponentException('No Email Theme found.');
        }

        $emailTheme->email = $this;

        return $this->_emailTheme = $emailTheme;
    }

    public function setEmailTheme(?EmailTheme $emailTheme): void
    {
        $this->_emailTheme = $emailTheme;
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

        $mailer->send($this, $this->getMailerInstructions());
    }

    public function getAdditionalButtons(): string
    {
        $emailType = $this->getEmailType();

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
                    'onclick' => 'window.SendEmailModal(' . $this->id . ')',
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
        $emailType = $this->getEmailType();

        return UrlHelper::cpUrl('sprout/email/' . $emailType::refHandle() . '/edit/' . $this->getCanonicalId());
    }

    public function getPostEditUrl(): ?string
    {
        $emailType = $this->getEmailType();

        return UrlHelper::cpUrl('sprout/email/' . $emailType::refHandle());
    }

    public function getFieldLayout(): ?FieldLayout
    {
        // Memoizing the field layout breaks the UI when the Theme switches
        // and generates the first provisional draft. The user then has to
        // reload or trigger another draft before the Content tab layout gets updated
        // with the new email theme layout.
        //if ($this->_fieldLayout) {
        //    return $this->_fieldLayout;
        //}

        $twigExpressionMessage1 = Craft::t('sprout-module-mailer', 'This can use a Twig Expression and reference Notification Event variables.');
        $twigExpressionMessage2 = Craft::t('sprout-module-mailer', 'This can use a Twig Expression and reference Notification Event and Recipient variables.');

        $fieldLayout = new FieldLayout([
            'type' => static::class,
        ]);

        $emailType = $this->getEmailType();
        /** @var Element|string $emailElementType */
        $emailElementType = $emailType::elementType();

        $themes = EmailThemeHelper::getEmailThemes();

        $themeTabs = array_map(static function(EmailTheme $theme) use ($emailElementType, $fieldLayout, $twigExpressionMessage2) {
            $elementCondition = $emailElementType::createCondition();
            $rule = new EmailThemeConditionRule();
            $rule->setValues([$theme->uid]);
            $elementCondition->addConditionRule($rule);

            $tab = $theme->getFieldLayout()->getTabs()[0] ?? null;
            $tab->layout = $fieldLayout;
            $tab->elementCondition = $elementCondition;

            foreach ($tab->elements as $element) {
                if ($element instanceof TextareaField && $element->attribute === 'defaultMessage') {
                    $element->tip = $twigExpressionMessage2;
                }
            }

            return $tab;
        }, $themes);

        $mailers = MailerHelper::getMailers();

        $mailerTabs = array_map(static function($mailer) use ($emailElementType, $fieldLayout, $twigExpressionMessage1) {
            $elementCondition = $emailElementType::createCondition();
            $rule = new MailerConditionRule();
            $rule->setValues([$mailer->uid]);
            $elementCondition->addConditionRule($rule);

            $tab = $mailer->getFieldLayout()->getTabs()[0] ?? [];
            $tab->layout = $fieldLayout;
            $tab->elementCondition = $elementCondition;

            foreach ($tab->elements as $element) {
                if ($element instanceof ToField) {
                    $element->tip = $twigExpressionMessage1;
                }
            }

            return $tab;
        }, $mailers);

        $elementCondition = $emailElementType::createCondition();
        $rule = new PreheaderTextConditionRule();
        $elementCondition->addConditionRule($rule);

        $subjectTab = new FieldLayoutTab();
        $subjectTab->layout = $fieldLayout;
        $subjectTab->name = Craft::t('sprout-module-mailer', 'Subject');
        $subjectTab->sortOrder = -1;
        $subjectTab->uid = 'SPROUT-UID-EMAIL-SUBJECT-TAB';
        $subjectTab->setElements([
            new TitleField([
                'label' => Craft::t('sprout-module-mailer', 'Email Name'),
                'uid' => 'SPROUT-UID-EMAIL-TITLE-FIELD',
            ]),
            new HorizontalRule([
                'uid' => 'SPROUT-UID-EMAIL-HORIZONTAL-RULE-SUBJECT-TAB-1',
            ]),
            new SubjectLineField([
                'tip' => $twigExpressionMessage2,
                'uid' => 'SPROUT-UID-EMAIL-SUBJECT-LINE-FIELD',
            ]),
            new PreheaderTextField([
                'elementCondition' => $elementCondition,
                'tip' => $twigExpressionMessage2,
                'uid' => 'SPROUT-UID-EMAIL-PREHEADER-FIELD',
            ]),
            new TextField([
                'type' => 'hidden',
                'name' => 'type',
                'attribute' => 'type',
                'containerAttributes' => [
                    'class' => 'hidden',
                ],
                'uid' => 'SPROUT-UID-EMAIL-EMAIL-TYPE-FIELD',
            ]),
            new TextField([
                'type' => 'hidden',
                'name' => 'emailThemeUid',
                'attribute' => 'emailThemeUid',
                'containerAttributes' => [
                    'class' => 'hidden',
                ],
                'uid' => 'SPROUT-UID-EMAIL-THEME-UID-FIELD',
            ]),
        ]);

        $newTabs = array_merge(
            [$subjectTab],
            $mailerTabs,
            [$emailType::getFieldLayoutTab($fieldLayout)],
            $themeTabs,
        );

        $fieldLayout->setTabs($newTabs);

        return $this->_fieldLayout = $fieldLayout;
    }

    public function getSidebarHtml(bool $static): string
    {
        $currentUser = Craft::$app->getUser()->getIdentity();

        return $currentUser->admin
            ? Html::tag('div', '', [
                'id' => 'sprout-notification-event-tip',
            ]) : "\n";
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
        $emailElementRecord->defaultMessage = $this->defaultMessage;

        $emailElementRecord->emailThemeUid = $this->emailThemeUid;

        $emailElementRecord->mailerUid = $this->mailerUid;
        $emailElementRecord->mailerInstructionsSettings = $this->mailerInstructionsSettings;

        $emailType = $this->getEmailType();
        $emailTypeSettings = $emailType->prepareEmailTypeSettingsForDb($this->emailTypeSettings);

        $emailElementRecord->type = $this->type;
        $emailElementRecord->emailTypeSettings = $emailTypeSettings;

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
        if ($this->getEmailType()->canBeDisabled()) {
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
                'template' => $this->getEmailTheme()->getHtmlEmailTemplate(),
                'variables' => [
                    'email' => $this,
                ],
            ],
        ];
    }

    protected function metadata(): array
    {
        return [
            Craft::t('sprout-module-mailer', 'Email Type') => $this->getEmailType()::displayName(),
            Craft::t('sprout-module-mailer', 'Email Theme') => $this->getEmailTheme()->name,
            Craft::t('sprout-module-mailer', 'Mailer') => $this->getMailer()->name,
        ];
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        // Subject
        $rules[] = [['title', 'subjectLine'], 'required', 'except' => self::SCENARIO_ESSENTIALS];
        $rules[] = [['preheaderText'], 'safe'];
        $rules[] = [['defaultMessage'], 'safe'];

        $rules[] = [['emailThemeUid'], 'safe'];
        $rules[] = [['mailerUid'], 'safe'];

        $rules[] = [['emailType'], 'safe'];
        $rules[] = [['emailTypeSettings'], 'safe'];
        $rules[] = [['mailerInstructionsSettings'], 'safe'];

        return $rules;
    }
}
