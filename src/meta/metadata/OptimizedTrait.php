<?php

namespace BarrelStrength\Sprout\meta\metadata;

use BarrelStrength\Sprout\meta\MetaModule;
use Craft;
use PhpScience\TextRank\TextRankFacade;
use PhpScience\TextRank\Tool\StopWords\English;
use PhpScience\TextRank\Tool\StopWords\French;
use PhpScience\TextRank\Tool\StopWords\German;
use PhpScience\TextRank\Tool\StopWords\Italian;
use PhpScience\TextRank\Tool\StopWords\Norwegian;
use PhpScience\TextRank\Tool\StopWords\Spanish;
use RuntimeException;

trait OptimizedTrait
{
    protected ?string $optimizedTitle = null;

    protected ?string $optimizedDescription = null;

    protected ?int $optimizedImage = null;

    protected ?string $optimizedKeywords = null;

    protected ?string $canonical = null;

    public function getOptimizedTitle(): ?string
    {
        if ($this->optimizedTitle || $this->getRawDataOnly()) {
            return $this->optimizedTitle;
        }

        $element = MetaModule::getInstance()->optimizeMetadata->element;
        $elementMetadataField = MetaModule::getInstance()->optimizeMetadata->elementMetadataField;

        $optimizedTitleFieldSetting = $elementMetadataField->optimizedTitleField ?? '';

        $title = match (true) {
            $optimizedTitleFieldSetting === 'elementTitle' => $element->title,
            $optimizedTitleFieldSetting === '' => $this->optimizedTitle,
            is_numeric($optimizedTitleFieldSetting) => OptimizeMetadataHelper::getSelectedFieldForOptimizedMetadata($optimizedTitleFieldSetting),
            default => Craft::$app->getView()->renderObjectTemplate($optimizedTitleFieldSetting, $element),
        };

        return $title ?: null;
    }

    public function setOptimizedTitle($value): void
    {
        $this->optimizedTitle = $value;
    }

    public function getOptimizedDescription(): ?string
    {
        if ($this->optimizedDescription || $this->getRawDataOnly()) {
            return $this->optimizedDescription;
        }

        $descriptionLength = MetaModule::getInstance()->elementMetadata->getDescriptionLength();

        $element = MetaModule::getInstance()->optimizeMetadata->element;
        $elementMetadataField = MetaModule::getInstance()->optimizeMetadata->elementMetadataField;

        $optimizedDescriptionFieldSetting = $elementMetadataField->optimizedDescriptionField ?? '';

        $description = match (true) {
            $optimizedDescriptionFieldSetting === '' => $this->optimizedDescription ?? null,
            is_numeric($optimizedDescriptionFieldSetting) => OptimizeMetadataHelper::getSelectedFieldForOptimizedMetadata($optimizedDescriptionFieldSetting),
            default => Craft::$app->view->renderObjectTemplate($optimizedDescriptionFieldSetting, $element),
        };

        // Just save the first 255 characters (we only output 160...)
        $description = mb_substr(trim($description), 0, 255);

        if ($description) {
            return mb_substr($description, 0, $descriptionLength) ?: null;
        }

        return null;
    }

    public function setOptimizedDescription($value): void
    {
        $this->optimizedDescription = $value;
    }

    public function getOptimizedImage()
    {
        if ($this->optimizedImage || $this->getRawDataOnly()) {
            $imageId = $this->optimizedImage;
            if (is_array($this->optimizedImage)) {
                $imageId = $this->optimizedImage[0] ?? null;
            }

            return $imageId;
        }

        $optimizedImageId = $this->normalizeImageValue($this->optimizedImage);

        if ($optimizedImageId) {
            return $optimizedImageId;
        }

        return MetaModule::getInstance()->optimizeMetadata->globals->getIdentity()['image'] ?? null;
    }

    public function setOptimizedImage($value): void
    {
        $this->optimizedImage = is_array($value) ? $value[0] ?? null : $value;
    }

    public function getOptimizedKeywords(): ?string
    {
        if ($this->optimizedKeywords || $this->getRawDataOnly()) {
            return $this->optimizedKeywords;
        }

        $keywords = $this->optimizedKeywords;

        $element = MetaModule::getInstance()->optimizeMetadata->element;
        $elementMetadataField = MetaModule::getInstance()->optimizeMetadata->elementMetadataField;

        $optimizedKeywordsFieldSetting = $elementMetadataField->optimizedKeywordsField ?? '';

        if (true == ($optimizedKeywordsFieldSetting === '')) {
            $keywords = $this->optimizedKeywords ?? null;
        } elseif (true == is_numeric($optimizedKeywordsFieldSetting)) {
            $bigKeywords = OptimizeMetadataHelper::getSelectedFieldForOptimizedMetadata($optimizedKeywordsFieldSetting);
            $keywords = null;
            if ($bigKeywords) {
                $textRankApi = new TextRankFacade();

                $stopWordsMap = [
                    'en' => English::class,
                    'fr' => French::class,
                    'de' => German::class,
                    'it' => Italian::class,
                    'nn' => Norwegian::class,
                    'es' => Spanish::class,
                ];

                $language = $element->getSite()->language;
                $languagePrefixArray = explode('-', $language);

                $stopWordsClass = $stopWordsMap['en'];

                if ($languagePrefixArray !== []) {
                    $languagePrefix = $languagePrefixArray[0];

                    if (isset($stopWordsMap[$languagePrefix])) {
                        $stopWordsClass = $stopWordsMap[$languagePrefix];
                    }
                }

                $stopWords = new $stopWordsClass();

                try {
                    $textRankApi->setStopWords($stopWords);

                    $rankedKeywords = $textRankApi->getOnlyKeyWords($bigKeywords);
                    $fiveKeywords = array_keys(array_slice($rankedKeywords, 0, 5));
                    $keywords = implode(',', $fiveKeywords);
                } catch (RuntimeException) {
                    // Cannot detect the language of the text, maybe to short.
                    $keywords = null;
                }
            }
        }

        return $keywords;
    }

    public function setOptimizedKeywords($value = null): void
    {
        $this->optimizedKeywords = $value;
    }

    public function getCanonical(): ?string
    {
        if ($this->canonical || $this->getRawDataOnly()) {
            return $this->canonical;
        }

        return OptimizeMetadataHelper::getCanonical($this->canonical);
    }

    public function setCanonical($value): void
    {
        $this->canonical = $value;
    }
}
