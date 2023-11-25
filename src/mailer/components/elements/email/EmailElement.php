<?php

namespace BarrelStrength\Sprout\mailer\components\elements\email;

use BarrelStrength\Sprout\core\Sprout;
use BarrelStrength\Sprout\mailer\components\elements\email\conditions\PreheaderTextConditionRule;
use BarrelStrength\Sprout\mailer\components\elements\email\fieldlayoutelements\PreheaderTextField;
use BarrelStrength\Sprout\mailer\components\elements\email\fieldlayoutelements\SubjectLineField;
use BarrelStrength\Sprout\mailer\components\emailtypes\fieldlayoutfields\DefaultMessageField;
use BarrelStrength\Sprout\mailer\components\mailers\SystemMailer;
use BarrelStrength\Sprout\mailer\emailtypes\EmailType;
use BarrelStrength\Sprout\mailer\emailtypes\EmailTypeHelper;
use BarrelStrength\Sprout\mailer\emailvariants\EmailVariant;
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
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\web\CpScreenResponseBehavior;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Validation\RFCValidation;
use http\Exception\InvalidArgumentException;
use yii\base\Model;
use yii\web\Response;

/**
 * @property null|EmailVariant $emailVariant
 * @property null|Mailer $mailer
 */
class EmailElement extends Element implements EmailPreviewInterface
{
    use EmailPreviewTrait;

    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    // Email Variant Statuses

    public const ENABLED = 'enabled';

    public const PENDING = 'pending';

    // Send Workflow

    public const DISABLED = 'disabled';

    //-------  SUBJECT --------

    /**
     * The Email Variant of this Email Element
     */
    public ?string $emailVariantType = null;

    /**
     * The Subject Line of your email. Your title will also default to the Subject Line unless you set a Title Format.
     */
    public string $subjectLine = '';

    public string $preheaderText = '';

    public string $defaultMessage = '';

    public ?string $emailTypeUid = null;

    public array $emailVariantSettings = [];

    public ?string $mailerUid = null;

    public array $mailerInstructionsSettings = [];

    protected ?EmailType $_emailType = null;

    protected ?EmailVariant $_emailVariant = null;

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

    public static function defaultTableAttributes(string $source): array
    {
        return [
            'subjectLine',
            'sendTest',
            'preview',
        ];
    }

    protected static function defineTableAttributes(): array
    {
        return [
            'subjectLine' => ['label' => Craft::t('sprout-module-mailer', 'Subject Line')],
            'sendTest' => ['label' => Craft::t('sprout-module-mailer', 'Send Test')],
            'preview' => ['label' => Craft::t('sprout-module-mailer', 'Preview'), 'icon' => 'view'],
            'emailType' => ['label' => Craft::t('sprout-module-mailer', 'Email Type')],
            'id' => ['label' => Craft::t('sprout-module-mailer', 'ID')],
            'uid' => ['label' => Craft::t('sprout-module-mailer', 'UID')],
            'dateCreated' => ['label' => Craft::t('sprout-module-mailer', 'Date Created')],
            'dateUpdated' => ['label' => Craft::t('sprout-module-mailer', 'Date Updated')],
        ];
    }

    protected static function defineSortOptions(): array
    {
        return [
            'title' => Craft::t('sprout-module-mailer', 'Name', [
                'emailVariant' => static::displayName(),
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
            'id' => Craft::t('sprout-module-mailer', 'ID'),
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

        $emailVariant = $this->getEmailVariant();

        $crumbs[] = [
            'label' => $emailVariant::displayName(),
            'url' => UrlHelper::url('sprout/email/' . $emailVariant::refHandle()),
        ];

        /** @var Response|CpScreenResponseBehavior $response */
        $response->crumbs($crumbs);
    }

    public function getMailer(): Mailer
    {
        if ($this->_mailer) {
            return $this->_mailer;
        }

        $emailType = $this->getEmailType();

        $emailVariant = $this->getEmailVariant();

        if ($emailType->mailerUid === MailerHelper::CRAFT_MAILER_SETTINGS) {
            return $emailVariant::getDefaultMailer();
        }

        return MailerHelper::getMailerByUid($emailType->mailerUid);
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
        $preparedMailerInstructionsSettings = $mailer->prepareMailerInstructionSettingsForEmail($this->mailerInstructionsSettings);
        $mailerInstructionsSettings->setAttributes($preparedMailerInstructionsSettings, false);

        $this->_mailerInstructionsSettingsModel = $mailerInstructionsSettings;

        return $this->_mailerInstructionsSettingsModel;
    }

    public function setEmailVariant($emailVariant): void
    {
        $this->_emailVariant = $emailVariant;
    }

    public function getEmailVariant(): EmailVariant
    {
        if ($this->_emailVariant !== null) {
            return $this->_emailVariant;
        }

        $emailVariant = new $this->emailVariantType();
        $emailVariant->setAttributes($this->emailVariantSettings, false);

        $this->_emailVariant = $emailVariant;

        return $this->_emailVariant;
    }

    /**
     * Returns the Email Variant contract for this email
     *
     * Email Variant determines:
     * $fieldLayout
     * $emailTypeUid
     */
    public function getEmailType(): EmailType
    {
        if ($this->_emailType) {
            return $this->_emailType;
        }

        $emailType = EmailTypeHelper::getEmailTypeByUid($this->emailTypeUid);

        if (!$emailType) {
            $emailType = EmailTypeHelper::getDefaultEmailType();
        }

        if (!$emailType) {
            throw new MissingComponentException('No Email Type found.');
        }

        $emailType->email = $this;

        return $this->_emailType = $emailType;
    }

    public function setEmailType(?EmailType $emailType): void
    {
        $this->_emailType = $emailType;
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
        $emailVariant = $this->getEmailVariant();

        return $emailVariant::getAdditionalButtonsHtml($this) . parent::getAdditionalButtons();
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

            case 'emailType':

                return $this->getEmailType()->name;
        }

        return parent::getTableAttributeHtml($attribute);
    }

    public function getPreviewType(): string
    {
        return self::EMAIL_TEMPLATE_TYPE_DYNAMIC;
    }

    public function cpEditUrl(): ?string
    {
        $emailVariant = $this->getEmailVariant();

        return UrlHelper::cpUrl('sprout/email/' . $emailVariant::refHandle() . '/edit/' . $this->getCanonicalId());
    }

    public function getPostEditUrl(): ?string
    {
        $emailVariant = $this->getEmailVariant();

        return UrlHelper::cpUrl('sprout/email/' . $emailVariant::refHandle());
    }

    public function getFieldLayout(): ?FieldLayout
    {
        //if ($this->_fieldLayout) {
        //    return $this->_fieldLayout;
        //}

        $twigExpressionMessage1 = Craft::t('sprout-module-mailer', 'This can use a Twig Shortcut Syntax and reference Notification Event variables.');
        $twigExpressionMessage2 = Craft::t('sprout-module-mailer', 'This can use a Twig Shortcut Syntax and reference Notification Event and Recipient variables.');

        $fieldLayout = new FieldLayout([
            'type' => static::class,
        ]);

        $emailVariant = $this->getEmailVariant();
        /** @var Element|string $emailElementType */
        $emailElementType = $emailVariant::elementType();

        //$emailTypes = EmailTypeHelper::getEmailTypes();
        $emailType = $this->getEmailType();
        $emailTypeTabs = $emailType->getFieldLayout()?->getTabs() ?? [];

        // Loop through the fields of each email type tab and add a tip to the default message field
        $emailTypeTabsWithMessages = array_map(static function(FieldLayoutTab $tab) use ($twigExpressionMessage2) {
            $newElements = [];
            foreach ($tab->elements as $element) {
                if ($element instanceof TextareaField && $element->attribute === DefaultMessageField::FIELD_LAYOUT_ATTRIBUTE) {
                    $element->tip = $twigExpressionMessage2;
                }
                $newElements[] = $element;
            }
            $tab->setElements($newElements);

            return $tab;
        }, $emailTypeTabs);

        $mailerTab = $this->getMailer()->getFieldLayout()?->getTabs()[0] ?? [];

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
                'name' => 'emailTypeUid',
                'attribute' => 'emailTypeUid',
                'containerAttributes' => [
                    'class' => 'hidden',
                ],
                'uid' => 'SPROUT-UID-EMAIL-TYPE-UID-FIELD',
            ]),
        ]);

        $newTabs = array_merge(
            [$subjectTab],
            [$mailerTab],
            [$emailVariant::getFieldLayoutTab($fieldLayout)],
            $emailTypeTabsWithMessages,
        );

        $fieldLayout->setTabs($newTabs);

        return $this->_fieldLayout = $fieldLayout;
    }

    public function getSidebarHtml(bool $static): string
    {
        $currentUser = Craft::$app->getUser()->getIdentity();

        $tipHtml = $currentUser->admin
            ? Html::tag('div', '', [
                'id' => 'sprout-notification-event-tip',
            ]) : "\n";

        $statusHtml = $this->statusFieldHtml();

        return $statusHtml . $tipHtml;
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

        $emailElementRecord->emailTypeUid = $this->emailTypeUid;

        $mailer = $this->getMailer();
        $emailElementRecord->mailerInstructionsSettings = $mailer->prepareMailerInstructionSettingsForDb($this->mailerInstructionsSettings);

        $emailVariant = $this->getEmailVariant();
        $emailVariantSettings = $emailVariant->prepareEmailVariantSettingsForDb($this->emailVariantSettings);

        $emailElementRecord->emailVariantType = $this->emailVariantType;
        $emailElementRecord->emailVariantSettings = $emailVariantSettings;

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
        if ($this->getEmailVariant()->canBeDisabled()) {

            $statusField = Cp::lightswitchFieldHtml([
                'id' => 'enabled',
                'label' => Craft::t('app', 'Enabled'),
                'name' => 'enabled',
                'on' => $this->enabled,
                'disabled' => $this->getIsRevision(),
                'status' => $this->getAttributeStatus('enabled'),
            ]);

            return Html::tag('div', $statusField, ['class' => 'meta']);
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
                'template' => $this->getEmailType()->getHtmlEmailTemplate(),
                'variables' => [
                    'email' => $this,
                ],
            ],
        ];
    }

    protected function metadata(): array
    {
        return [
            Craft::t('sprout-module-mailer', 'Email Variant') => $this->getEmailVariant()::displayName(),
            Craft::t('sprout-module-mailer', 'Email Type') => $this->getEmailType()->name,
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

        $rules[] = [['emailTypeUid'], 'safe'];
        $rules[] = [['mailerUid'], 'safe'];

        $rules[] = [['emailVariant'], 'safe'];
        $rules[] = [['emailVariantSettings'], 'safe'];
        $rules[] = [['mailerInstructionsSettings'], 'validateMailerInstructionsSettings', 'on' => self::SCENARIO_LIVE];

        return $rules;
    }

    public function validateMailerInstructionsSettings(): void
    {
        $mailer = $this->getMailer();

        /** @var Model $mailerInstructionsSettings */
        $mailerInstructionsSettings = $mailer->createMailerInstructionsSettingsModel();
        $mailerInstructionsSettings->setAttributes($this->mailerInstructionsSettings, false);
        $mailerInstructionsSettings->mailer = $mailer;

        if (!$mailerInstructionsSettings->validate()) {
            // Adding the error to the Element makes sure the Mailer tab is highlighted with errors
            $this->addError('mailerInstructionsSettings', Craft::t('sprout-module-mailer', 'Invalid Mailer Instructions Settings.'));
            // Adding the errors to the model, makes sure the errors are displayed for the fields
            $this->addModelErrors($mailerInstructionsSettings);
        }
    }
}
