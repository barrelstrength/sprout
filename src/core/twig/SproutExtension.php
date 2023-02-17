<?php

namespace BarrelStrength\Sprout\core\twig;

use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFunction;

class SproutExtension extends AbstractExtension implements GlobalsInterface
{
    public function getGlobals(): array
    {
        return [
            'sprout' => new SproutVariable(),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('sproutAssetUrl', [TemplateHelper::class, 'getSproutAssetUrl']),
            new TwigFunction('sproutDynamicCsrfInput', [TemplateHelper::class, 'getDynamicCsrfInput'], ['is_safe' => ['html']]),
            new TwigFunction('sproutTemplateFolderSuggestions', [TemplateHelper::class, 'getTemplateFolderSuggestions']),
            new TwigFunction('sproutConfigWarning', [TemplateHelper::class, 'getConfigWarning']),
        ];
    }
}
