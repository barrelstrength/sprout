<?php

namespace BarrelStrength\Sprout\mailer\emailthemes;

use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use Craft;
use craft\base\SavableComponent;
use craft\models\FieldLayout;
use craft\web\View;
use League\HTMLToMarkdown\HtmlConverter;

/**
 * @property string $textBody
 * @property string $htmlBody
 * @property null|FieldLayout $fieldLayout
 */
abstract class EmailTheme extends SavableComponent implements EmailThemeInterface
{
    public ?string $name = null;

    public ?string $htmlEmailTemplate = null;

    public ?string $textEmailTemplate = null;

    public ?string $copyPasteEmailTemplate = null;

    public ?EmailElement $email = null;

    private array $_templateVariables = [];

    public ?string $uid = null;

    private ?FieldLayout $_fieldLayout = null;

    private ?string $_htmlBody = null;

    private ?string $_textBody = null;

    public function name(): ?string
    {
        return $this->name;
    }

    abstract public static function getHandle(): string;

    public static function isEditable(): bool
    {
        return false;
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

    public function getFieldLayout(): FieldLayout
    {
        if ($this->_fieldLayout) {
            return $this->_fieldLayout;
        }

        return new FieldLayout([
            'type' => EmailElement::class,
        ]);
    }

    public function setFieldLayout(?FieldLayout $fieldLayout): void
    {
        $this->_fieldLayout = $fieldLayout;
    }

    public function getHtmlBody($recipient = null): string
    {
        if (!$this->_htmlBody) {
            $this->processThemeTemplates($recipient);
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
            $this->processThemeTemplates($recipient);
        }

        return $this->_textBody;
    }

    public function setTextBody(string $text): void
    {
        $this->_textBody = $text;
    }

    public function getTextEmailTemplate(): ?string
    {
        if (!Craft::$app->getView()->doesTemplateExist($this->textEmailTemplate)) {
            return null;
        }

        return $this->textEmailTemplate;
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
            'handle' => $this::getHandle(),
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

    protected function processThemeTemplates($recipient = null): void
    {
        $view = Craft::$app->getView();

        $oldTemplateMode = $view->getTemplateMode();
        $view->setTemplateMode(View::TEMPLATE_MODE_SITE);

        // Craft::dd($this->email->getEmailTypeSettings()->getObjectVariable());
        // @todo - add dynamic support for objects
        $htmlBody = Craft::$app->getView()->renderTemplate(
            $this->getIncludePath(),
            $this->getTemplateVariables()
        );

        // Converts html body to text email if no .txt
        if (Craft::$app->getView()->doesTemplateExist($this->textEmailTemplate)) {
            $textBody = Craft::$app->getView()->renderTemplate(
                $this->textEmailTemplate,
                $this->getTemplateVariables()
            );
        } else {
            $converter = new HtmlConverter([
                'strip_tags' => true,
            ]);

            // For more advanced html templates, conversion may be tougher. Minifying the HTML
            // can help and ensuring that content is wrapped in proper tags that adds spaces between
            // things in Markdown, like <p> tags or <h1> tags and not just <td> or <div>, etc.
            $markdown = $converter->convert($htmlBody);

            $textBody = trim($markdown);
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
}
