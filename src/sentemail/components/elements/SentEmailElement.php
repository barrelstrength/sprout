<?php

namespace BarrelStrength\Sprout\sentemail\components\elements;

use BarrelStrength\Sprout\core\Sprout;
use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\mailer\components\elements\email\EmailPreviewInterface;
use BarrelStrength\Sprout\mailer\components\elements\email\EmailPreviewTrait;
use BarrelStrength\Sprout\sentemail\sentemail\SentEmailDetails;
use BarrelStrength\Sprout\sentemail\sentemail\SentEmailRecord;
use BarrelStrength\Sprout\sentemail\SentEmailModule;
use Craft;
use craft\base\Element;
use craft\elements\actions\Delete;
use craft\elements\db\ElementQueryInterface;
use craft\elements\User;
use craft\helpers\Html;
use yii\base\Exception;

class SentEmailElement extends Element implements EmailPreviewInterface
{
    use EmailPreviewTrait;

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public string $subjectLine;

    public bool $enableFileAttachments = false;

    // Sender Info

    public string $fromName;

    public string $fromEmail;

    public string $toEmail;

    public string $textBody;

    public string $htmlBody;

    public array $info = [];

    public bool $sent = false;

    protected array $fields = [];

    public static function displayName(): string
    {
        return Craft::t('sprout-module-sent-email', 'Sent Email');
    }

    public static function lowerDisplayName(): string
    {
        return Craft::t('sprout-module-sent-email', 'sent email');
    }

    public static function pluralDisplayName(): string
    {
        return Craft::t('sprout-module-sent-email', 'Sent Emails');
    }

    public static function pluralLowerDisplayName(): string
    {
        return Craft::t('sprout-module-sent-email', 'sent emails');
    }

    public static function hasTitles(): bool
    {
        return true;
    }

    public static function hasStatuses(): bool
    {
        return true;
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_SENT => [
                'label' => Craft::t('sprout-module-sent-email', 'Sent'),
                'color' => 'green',
            ],
            self::STATUS_FAILED => [
                'label' => Craft::t('sprout-module-sent-email', 'Failed'),
                'color' => 'red',
            ],
        ];
    }

    public function getStatus(): ?string
    {
        if ($this->sent) {
            return self::STATUS_SENT;
        }

        return self::STATUS_FAILED;
    }

    public static function find(): ElementQueryInterface
    {
        return new SentEmailElementQuery(static::class);
    }

    public static function indexHtml(ElementQueryInterface $elementQuery, ?array $disabledElementIds, array $viewState, ?string $sourceKey, ?string $context, bool $includeContainer, bool $showCheckboxes): string
    {
        $html = parent::indexHtml($elementQuery, $disabledElementIds, $viewState, $sourceKey, $context, $includeContainer,
            $showCheckboxes);

        Sprout::getInstance()->vite->register('mailer/SendEmailModal.js', false);
        Sprout::getInstance()->vite->register('sent-email/SentEmailDetailsModal.js', false);

        return $html;
    }

    protected static function defineSources(string $context = null): array
    {
        return [
            [
                'key' => '*',
                'label' => Craft::t('sprout-module-sent-email', 'All Sent Emails'),
                'defaultSort' => ['dateCreated', 'desc'],
            ],
        ];
    }

    protected static function defineSearchableAttributes(): array
    {
        return [
            'toEmail',
            'subjectLine',
        ];
    }

    protected static function defineSortOptions(): array
    {
        return [
            [
                'label' => Craft::t('sprout-module-sent-email', 'Date Sent'),
                'orderBy' => 'elements.dateCreated',
                'attribute' => 'dateCreated',
                'defaultDir' => 'desc',
            ],
        ];
    }

    protected static function defineTableAttributes(): array
    {
        $attributes = [
            'dateCreated' => ['label' => Craft::t('sprout-module-sent-email', 'Date Sent')],
            'toEmail' => ['label' => Craft::t('sprout-module-sent-email', 'Recipient')],
            'subjectLine' => ['label' => Craft::t('sprout-module-sent-email', 'Subject')],
            'info' => ['label' => Craft::t('sprout-module-sent-email', 'Info')],
        ];

        if (Craft::$app->getUser()->checkPermission('sprout-module-sent-email:resendSentEmail')) {
            $attributes['resend'] = ['label' => Craft::t('sprout-module-sent-email', 'Resend')];
        }

        $attributes['preview'] = ['label' => Craft::t('sprout-module-sent-email', 'Preview'), 'icon' => 'view'];

        return $attributes;
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        return [
            'title',
            'subjectLine',
            'dateCreated',
            'info',
            'resend',
            'preview',
        ];
    }

    protected static function defineActions(string $source = null): array
    {
        $actions = parent::defineActions($source);

        $actions[] = Delete::class;

        return $actions;
    }

    public function __toString(): string
    {
        return $this->toEmail;
    }

    public function getTableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {

            case 'info':

                return Html::tag('a', Craft::t('sprout-module-sent-email', 'Info'), [
                    'href' => '#',
                    'class' => 'sprout-sent-email-details-btn btn small formsubmit',
                    'data-email-id' => $this->id,
                ]);

            case 'resend':

                return Html::tag('a', Craft::t('sprout-module-sent-email', 'Resend'), [
                    'href' => '#',
                    'class' => 'sprout-send-email-btn btn small formsubmit',
                    'data-email-id' => $this->id,
                    'data-get-send-email-html-action' => 'sprout-module-sent-email/sent-email/get-resend-modal-html',
                    'data-send-email-action' => 'sprout-module-sent-email/sent-email/resend-email',
                    'data-modal-title' => Craft::t('sprout-module-sent-email', 'Resend email'),
                    'data-modal-action-btn-label' => Craft::t('sprout-module-sent-email', 'Resend Now'),
                ]);

            case 'preview':

                return Html::tag('a', Craft::t('sprout-module-mailer', ''), [
                    'href' => '#',
                    'onClick' => 'window.open("' . $this->getPreviewUrl() . '", "_blank", "width=800,height=600,scrollbars=yes,resizable=yes"); return false;',
                    'rel' => 'noopener',
                    'class' => 'email-preview',
                    'data-icon' => 'view',
                    'data-element-type' => self::class,
                ]);
        }

        return parent::getTableAttributeHtml($attribute);
    }

    public function getLocaleNiceDateTime(): string
    {
        return $this->dateCreated->format('M j, Y H:i:s A');
    }

    public function afterSave(bool $isNew): void
    {
        // Get the entry record
        if (!$isNew) {
            $record = SentEmailRecord::findOne($this->id);

            if (!$record instanceof SentEmailRecord) {
                throw new Exception('Invalid campaign email ID: ' . $this->id);
            }
        } else {
            $record = new SentEmailRecord();
            $record->id = $this->id;
        }

        $record->title = $this->title;
        $record->subjectLine = $this->subjectLine;
        $record->fromEmail = $this->fromEmail;
        $record->fromName = $this->fromName;
        $record->toEmail = $this->toEmail;
        $record->textBody = $this->textBody;
        $record->htmlBody = $this->htmlBody;
        $record->info = $this->info;
        $record->sent = $this->sent;
        $record->dateCreated = $this->dateCreated;
        $record->dateUpdated = $this->dateUpdated;

        $record->save(false);

        // Update the entry's descendants, who may be using this entry's URI in their own URIs
        Craft::$app->getElements()->updateElementSlugAndUri($this);

        parent::afterSave($isNew);
    }

    public function canView(User $user): bool
    {
        return $user->can(SentEmailModule::p('viewSentEmail'));
    }

    public function canResend(User $user): bool
    {
        return $user->can(SentEmailModule::p('resendSentEmail'));
    }

    public function getDetails(): SentEmailDetails
    {
        $sentEmailDetails = new SentEmailDetails();
        $sentEmailDetails->setAttributes($this->info, false);

        return $sentEmailDetails;
    }

    public function getPreviewType(): string
    {
        return EmailElement::EMAIL_TEMPLATE_TYPE_STATIC;
    }
}
