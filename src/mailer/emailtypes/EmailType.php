<?php

namespace BarrelStrength\Sprout\mailer\emailtypes;

use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\mailer\components\emailtypes\fieldlayoutfields\OptionalDefaultMessageField;
use Craft;
use craft\base\SavableComponent;
use craft\events\DefineFieldLayoutFieldsEvent;
use craft\helpers\Html;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\web\View;
use League\HTMLToMarkdown\HtmlConverter;

/**
 * @property string $textBody
 * @property string $htmlBody
 * @property null|FieldLayout $fieldLayout
 */
abstract class EmailType extends SavableComponent implements EmailTypeInterface
{
    public ?string $name = null;

    public bool $displayPreheaderText = false;

    public ?string $htmlEmailTemplate = null;

    public ?string $textEmailTemplate = null;

    public ?string $copyPasteEmailTemplate = null;

    public ?string $mailerUid = null;

    public ?EmailElement $email = null;

    private array $_templateVariables = [];

    public ?string $uid = null;

    protected ?FieldLayout $_fieldLayout = null;

    private ?string $_htmlBody = null;

    private ?string $_textBody = null;

    public static function isEditable(): bool
    {
        return false;
    }

    final public function getCpEditUrl(): ?string
    {
        return UrlHelper::cpUrl('sprout/settings/email-types/edit/' . $this->uid);
    }

    public function getTemplateVariables(): array
    {
        return $this->_templateVariables;
    }

    public function addTemplateVariable($name, $value): void
    {
        $this->_templateVariables[$name] = $value;
    }

    public function addTemplateVariables(array $values = []): void
    {
        foreach ($values as $name => $value) {
            $this->_templateVariables[$name] = $value;
        }
    }

    public static function defineNativeFields(DefineFieldLayoutFieldsEvent $event): void
    {
        // @todo - for some reason this registers twice, so we can probably fix this logic somewhere else
        if (!in_array(OptionalDefaultMessageField::class, $event->fields, true)) {
            $event->fields[] = OptionalDefaultMessageField::class;
        }
    }

    public function getFieldLayout(): FieldLayout
    {
        if ($this->_fieldLayout) {
            return $this->_fieldLayout;
        }

        $fieldLayout = new FieldLayout([
            'type' => static::class,
        ]);

        return $this->_fieldLayout = $fieldLayout;
    }

    public function setFieldLayout(?FieldLayout $fieldLayout): void
    {
        $this->_fieldLayout = $fieldLayout;
    }

    public function getHtmlBody($recipient = null): string
    {
        if (!$this->_htmlBody) {
            $this->processEmailTemplates($recipient);
        }

        return $this->_htmlBody;
    }

    public function setHtmlBody(string $html): void
    {
        $this->_htmlBody = $html;
    }

    public function getTextBody($recipient = null): string
    {
        if (!$this->_textBody) {
            $this->processEmailTemplates($recipient);
        }

        return $this->_textBody;
    }

    public function setTextBody(string $text): void
    {
        $this->_textBody = $text;
    }

    public function getHtmlEmailTemplate(): ?string
    {
        if (!$template = Craft::getAlias($this->htmlEmailTemplate)) {
            return null;
        }

        if (!Craft::$app->getView()->doesTemplateExist($template)) {
            return null;
        }

        return $template;
    }

    public function getTextEmailTemplate(): ?string
    {
        if (!$template = Craft::getAlias($this->textEmailTemplate)) {
            return null;
        }

        if (!Craft::$app->getView()->doesTemplateExist($template)) {
            return null;
        }

        return $template;
    }

    protected function processEmailTemplates($recipient = null): void
    {
        $view = Craft::$app->getView();

        $oldTemplateMode = $view->getTemplateMode();
        $view->setTemplateMode(View::TEMPLATE_MODE_SITE);

        $htmlBody = Craft::$app->getView()->renderTemplate($this->getHtmlEmailTemplate(),
            $this->getTemplateVariables()
        );

        // Converts html body to text email if no .txt
        if ($this->getTextEmailTemplate() && Craft::$app->getView()->doesTemplateExist($this->getTextEmailTemplate())) {
            $textBody = Craft::$app->getView()->renderTemplate($this->getTextEmailTemplate(),
                $this->getTemplateVariables()
            );
        } else {
            $converter = new HtmlConverter([
                'remove_nodes' => 'head style script',
                'strip_tags' => true,
                'hard_break' => true,
            ]);

            // For more advanced html templates, conversion may be tougher. Minifying the HTML
            // can help and ensuring that content is wrapped in proper tags that adds spaces between
            // things in Markdown, like <p> tags or <h1> tags and not just <td> or <div>, etc.
            $markdown = $converter->convert($htmlBody);

            $textBody = Html::tag('pre', trim($markdown), [
                'style' => 'white-space: pre-wrap; word-wrap: break-word;',
            ]);
        }

        $view->setTemplateMode($oldTemplateMode);

        $this->setHtmlBody($htmlBody);
        $this->setTextBody($textBody);
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['name'], 'required'];
        $rules[] = [['htmlEmailTemplate'], 'required'];
        $rules[] = [['fieldLayout'], 'hasAtLeastOneField'];

        return $rules;
    }

    public function hasAtLeastOneField(): void
    {
        $tabs = $this->getFieldLayout()->getTabs();

        if (!count($tabs) || !count($tabs[0]->getElements())) {
            $this->addError('fieldLayout', Craft::t('sprout-module-mailer', 'Field layout must have at least one field.'));
        }
    }

    public function getConfig(): array
    {
        $config = [
            'type' => static::class,
            'name' => $this->name,
            'displayPreheaderText' => $this->displayPreheaderText,
            'htmlEmailTemplate' => $this->htmlEmailTemplate,
            'textEmailTemplate' => $this->textEmailTemplate,
            'copyPasteEmailTemplate' => $this->copyPasteEmailTemplate,
        ];

        $fieldLayout = $this->getFieldLayout();

        if ($fieldLayoutConfig = $fieldLayout->getConfig()) {
            $config['fieldLayouts'] = [
                $fieldLayout->uid => $fieldLayoutConfig,
            ];
        }

        return $config;
    }
}
