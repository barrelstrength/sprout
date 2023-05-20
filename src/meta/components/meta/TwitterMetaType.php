<?php

namespace BarrelStrength\Sprout\meta\components\meta;

use BarrelStrength\Sprout\meta\metadata\MetaImageTrait;
use BarrelStrength\Sprout\meta\metadata\MetaType;
use BarrelStrength\Sprout\meta\MetaModule;
use Craft;
use craft\base\Field;

class TwitterMetaType extends MetaType
{
    use MetaImageTrait;

    protected ?string $twitterCard = null;

    protected ?string $twitterSite = null;

    protected ?string $twitterCreator = null;

    protected ?string $twitterUrl = null;

    protected ?string $twitterTitle = null;

    protected ?string $twitterDescription = null;

    protected ?string $twitterImage = null;

    protected ?string $twitterTransform = null;

    private ?string $twitterProfileName = null;

    public static function displayName(): string
    {
        return Craft::t('sprout-module-meta', 'Twitter Card');
    }

    public function attributes(): array
    {
        $attributes = parent::attributes();
        $attributes[] = 'twitterCard';
        $attributes[] = 'twitterSite';
        $attributes[] = 'twitterCreator';
        $attributes[] = 'twitterTitle';
        $attributes[] = 'twitterDescription';
        $attributes[] = 'twitterUrl';
        $attributes[] = 'twitterImage';
        $attributes[] = 'twitterTransform';

        return $attributes;
    }

    public function getAttributesMapping(): array
    {
        return [
            'twitterCard' => 'twitter:card',
            'twitterSite' => 'twitter:site',
            'twitterCreator' => 'twitter:creator',
            'twitterTitle' => 'twitter:title',
            'twitterDescription' => 'twitter:description',
            'twitterUrl' => 'twitter:url',
            'twitterImage' => 'twitter:image',
        ];
    }

    public function getTwitterCard(): ?string
    {
        if ($this->twitterCard || $this->metadata->getRawDataOnly()) {
            return $this->twitterCard;
        }

        return MetaModule::getInstance()->optimizeMetadata->globals->getSettings()['defaultTwitterCard'] ?? 'summary';
    }

    public function setTwitterCard(?string $value): void
    {
        $this->twitterCard = $value;
    }

    public function getTwitterSite(): ?string
    {
        if ($this->twitterCreator || $this->metadata->getRawDataOnly()) {
            return $this->twitterSite;
        }

        return $this->getTwitterProfileName();
    }

    public function setTwitterSite(?string $value): void
    {
        $this->twitterSite = $value;
    }

    public function getTwitterCreator(): ?string
    {
        if ($this->twitterCreator || $this->metadata->getRawDataOnly()) {
            return $this->twitterCreator;
        }

        return $this->getTwitterProfileName();
    }

    public function setTwitterCreator(?string $value): void
    {
        $this->twitterCreator = $value;
    }

    public function getTwitterUrl(): ?string
    {
        if ($this->twitterUrl || $this->metadata->getRawDataOnly()) {
            return $this->twitterUrl;
        }

        return $this->metadata->getCanonical();
    }

    public function setTwitterUrl(?string $value): void
    {
        $this->twitterUrl = $value;
    }

    public function getTwitterTitle(): ?string
    {
        if ($this->twitterTitle || $this->metadata->getRawDataOnly()) {
            return $this->twitterTitle;
        }

        if ($optimizedTitle = $this->metadata->getOptimizedTitle()) {
            return trim($optimizedTitle) ?: null;
        }

        return trim(MetaModule::getInstance()->optimizeMetadata->globals->getIdentity()['name']);
    }

    public function setTwitterTitle(?string $value): void
    {
        $this->twitterTitle = $value;
    }

    public function getTwitterDescription(): ?string
    {
        $descriptionLength = MetaModule::getInstance()->elementMetadata->getDescriptionLength();

        if ($this->twitterDescription || $this->metadata->getRawDataOnly()) {
            return mb_substr($this->twitterDescription, 0, $descriptionLength) ?: null;
        }

        if ($optimizedDescription = $this->metadata->getOptimizedDescription()) {
            return mb_substr($optimizedDescription, 0, $descriptionLength) ?: null;
        }

        $globalDescription = MetaModule::getInstance()->optimizeMetadata->globals->getIdentity()['description'] ?? null;

        return mb_substr($globalDescription, 0, $descriptionLength) ?: null;
    }

    public function setTwitterDescription(?string $value): void
    {
        $this->twitterDescription = $value;
    }

    public function getTwitterImage()
    {
        if ($this->twitterImage || $this->metadata->getRawDataOnly()) {
            return $this->twitterImage;
        }

        if ($optimizedImage = $this->metadata->getOptimizedImage()) {
            return $this->normalizeImageValue($optimizedImage);
        }

        return MetaModule::getInstance()->optimizeMetadata->globals->getIdentity()['image'] ?? null;
    }

    public function setTwitterImage(?string $value): void
    {
        $this->twitterImage = is_array($value) ? $value[0] ?? null : $value;
    }

    public function getTwitterTransform(): ?string
    {
        if ($this->twitterTransform || $this->metadata->getRawDataOnly()) {
            return $this->twitterTransform;
        }

        return MetaModule::getInstance()->optimizeMetadata->globals->getSettings()['twitterTransform'] ?? null;
    }

    public function setTwitterTransform(?string $value): void
    {
        $this->twitterTransform = $value;
    }

    public function getHandle(): string
    {
        return 'twitterCard';
    }

    public function getIconPath(): string
    {
        return '@Sprout/Assets/dist/static/meta/icons/twitter.svg';
    }

    public function getSettingsHtml(Field $field): string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-meta/_components/fields/elementmetadata/blocks/twitter-card.twig', [
            'meta' => $this,
            'field' => $field,
        ]);
    }

    public function showMetaDetailsTab(): bool
    {
        return MetaModule::getInstance()->optimizeMetadata->elementMetadataField->showTwitter;
    }

    public function getMetaTagData(): array
    {
        $tagData = parent::getMetaTagData();

        if (isset($tagData['twitter:image'])) {
            $tagData['twitter:image'] = $this->prepareAssetMetaData($tagData['twitter:image'], $this->getTwitterTransform());
        }

        return $tagData;
    }

    /**
     * Check our Social Profile settings for a Twitter profile.
     * Return the first Twitter profile
     */
    public function getTwitterProfileName(): ?string
    {
        // Have we already done this?
        if ($this->twitterProfileName) {
            return $this->twitterProfileName;
        }

        $socialProfiles = MetaModule::getInstance()->optimizeMetadata->globals->getSocial();

        if ($socialProfiles === null) {
            return null;
        }

        $twitterProfileName = null;

        foreach ($socialProfiles as $profile) {
            $socialProfileNameFromPost = $profile[0] ?? null;
            $socialProfileNameFromSettings = $profile['profileName'] ?? null;

            // Support syntax for both POST data being saved and previous saved social settings
            if ($socialProfileNameFromPost === 'Twitter' || $socialProfileNameFromSettings === 'Twitter') {
                $twitterUrlFromPost = isset($socialProfileNameFromPost) ? $profile[1] : null;
                $twitterUrl = $socialProfileNameFromSettings !== null ? $profile['url'] : $twitterUrlFromPost;

                $twitterProfileName = '@' . mb_substr($twitterUrl, strrpos($twitterUrl, '/') + 1);

                break;
            }
        }

        // memoize it if we need it again
        $this->twitterProfileName = $twitterProfileName;

        return $twitterProfileName;
    }

}
