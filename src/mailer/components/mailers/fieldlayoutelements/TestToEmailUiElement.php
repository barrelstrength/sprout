<?php

namespace BarrelStrength\Sprout\mailer\components\mailers\fieldlayoutelements;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\BaseUiElement;
use craft\helpers\Html;
use yii\helpers\Markdown;

class TestToEmailUiElement extends BaseUiElement
{
    protected function selectorLabel(): string
    {
        return Craft::t('sprout-module-mailer', 'Test Email Warning');
    }

    protected function selectorIcon(): ?string
    {
        return '@appicons/alert.svg';
    }

    public function formHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        $testToEmailAddress = Craft::$app->getConfig()->getGeneral()->testToEmailAddress;

        $id = sprintf('warning%s', mt_rand());

        $message = Markdown::process(Html::encode(Craft::t('sprout-module-mailer', 'Test email found in general config. All messages will be sent to the testToEmailAddress: {email}', [
            'email' => $testToEmailAddress,
        ])));

        $blockquote = Html::tag('blockquote', $message, [
            'class' => 'note',
        ]);

        $html = Html::tag('div', $blockquote, [
            'id' => $id,
            'class' => 'readable',
        ]);

        return $testToEmailAddress ? $html : null;
    }
}
