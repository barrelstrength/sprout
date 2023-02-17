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

    public ?int $fieldLayoutId = null;

    public ?string $htmlEmailTemplatePath = null;

    public ?string $copyPasteEmailTemplatePath = null;

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

    public function isEditable(): bool
    {
        return true;
    }

    public function getTemplateMode(): string
    {
        return View::TEMPLATE_MODE_CP;
    }

    public function htmlEmailTemplatePath(): ?string
    {
        return $this->htmlEmailTemplatePath;
    }

    public function copyPasteEmailTemplatePath(): ?string
    {
        return $this->copyPasteEmailTemplatePath ?? $this->htmlEmailTemplatePath;
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

        if ($this->fieldLayoutId) {
            return Craft::$app->getFields()->getLayoutById($this->fieldLayoutId);
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
        $pathInfo = pathinfo($this->htmlEmailTemplatePath());

        $textPath = $pathInfo['dirname'] . DIRECTORY_SEPARATOR . $pathInfo['filename'] . '.txt';

        if (!Craft::$app->getView()->doesTemplateExist($textPath)) {
            return null;
        }

        return $textPath;
    }

    public function hasAtLeastOneField(): void
    {
        if (!count($this->getFieldLayout()->getCustomFields())) {
            $this->addError('fieldLayout', Craft::t('sprout-module-mailer', 'Field layout must have at least one field.'));
        }
    }

    public function getConfig(): array
    {
        $config = [
            'name' => $this->name,
            'handle' => $this->handle,
            'htmlEmailTemplatePath' => $this->htmlEmailTemplatePath,
            'copyPasteEmailTemplatePath' => $this->copyPasteEmailTemplatePath,
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
        $oldTemplatePath = $view->getTemplatesPath();

        $view->setTemplateMode($this->getTemplateMode());
        $view->setTemplatesPath($this->getTemplateRoot());
        //        Craft::dd($this->email->getEmailTypeSettings()->getObjectVariable());
        // @todo - add dynamic support for objects
        $htmlBody = Craft::$app->getView()->renderTemplate(
            $this->htmlEmailTemplatePath(),
            $this->getTemplateVariables()
        );

        // Converts html body to text email if no .txt
        if ($textEmailTemplate = $this->getTextEmailTemplate()) {
            $textBody = Craft::$app->getView()->renderTemplate(
                $textEmailTemplate,
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
        $view->setTemplatesPath($oldTemplatePath);

        $this->setHtmlBody($htmlBody);
        $this->setTextBody($textBody);
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['name'], 'required'];
        $rules[] = [['htmlEmailTemplatePath'], 'required'];
        $rules[] = [['fieldLayout'], 'hasAtLeastOneField'];

        return $rules;
    }
}
