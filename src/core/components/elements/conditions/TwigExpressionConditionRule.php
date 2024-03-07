<?php

namespace BarrelStrength\Sprout\core\components\elements\conditions;

use Craft;
use craft\base\conditions\BaseConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\helpers\Cp;
use craft\helpers\Html;
use yii\base\Exception;
use yii\db\QueryInterface;

class TwigExpressionConditionRule extends BaseConditionRule implements ElementConditionRuleInterface
{
    public string $twigExpression = '';

    public function getLabel(): string
    {
        return Craft::t('sprout-module-core', 'Twig Expression');
    }

    public function getExclusiveQueryParams(): array
    {
        return [
            'twigExpression',
        ];
    }

    public function getConfig(): array
    {
        return array_merge(parent::getConfig(), [
            'twigExpression' => $this->twigExpression,
        ]);
    }

    protected function inputHtml(): string
    {
        $ruleHtml = Html::hiddenLabel(Html::encode($this->getLabel()), 'twigExpression') .
            Cp::textHtml([
                'type' => 'text',
                'id' => 'twigExpression',
                'name' => 'twigExpression',
                'placeholder' => Craft::t('sprout-module-core', "{% if object.field == 'send' %}true{% endif %}"),
                'value' => $this->twigExpression,
                'autocomplete' => false,
                'class' => 'flex-grow flex-shrink code',
            ]);

        $instruction = Craft::t('sprout-module-core', "Twig expression matches if evaluates to 'true', '1', 'on', or 'yes'.");
        $complicatedMessage = Craft::t('sprout-module-core', 'This rule is intended for use in Notification Events and conditional layouts. The twig expression is evaluated after the element query is complete and does not change query results.');

        return
            Html::tag('div', $ruleHtml, [
                'class' => 'fullwidth',
            ]) .
            Html::tag('em', $instruction, [
                'class' => 'smalltext',
            ]) .
            Html::tag('span', $complicatedMessage, [
                'class' => 'info',
            ]);
    }

    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            [['twigExpression'], 'safe'],
        ]);
    }

    public function modifyQuery(QueryInterface $query): void
    {
        // No changes
    }

    public function matchElement(ElementInterface $element): bool
    {
        $twigExpression = trim($this->twigExpression);

        // No send rule = Always Send
        if (empty($twigExpression)) {
            return true;
        }

        // Evaluate Twig Expression
        try {
            $resultTemplate = Craft::$app->getView()->renderObjectTemplate(
                $twigExpression, $element
            );

            $value = trim($resultTemplate);
            if (filter_var($value, FILTER_VALIDATE_BOOLEAN)) {
                return true;
            }
        } catch (Exception $exception) {
            Craft::error($exception->getMessage(), __METHOD__);
        }

        return false;
    }
}
