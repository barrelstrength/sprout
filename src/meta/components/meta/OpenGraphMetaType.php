<?php

namespace BarrelStrength\Sprout\meta\components\meta;

use BarrelStrength\Sprout\meta\metadata\MetaImageTrait;
use BarrelStrength\Sprout\meta\metadata\MetaType;
use BarrelStrength\Sprout\meta\MetaModule;
use Craft;
use craft\base\Field;
use DateTime;
use DateTimeInterface;

class OpenGraphMetaType extends MetaType
{
    use MetaImageTrait;

    protected ?string $ogType = null;

    protected ?string $ogSiteName = null;

    protected ?string $ogAuthor = null;

    protected ?string $ogPublisher = null;

    protected ?string $ogUrl = null;

    protected ?string $ogTitle = null;

    protected ?string $ogDescription = null;

    protected ?string $ogImage = null;

    protected ?string $ogImageSecure = null;

    protected ?int $ogImageWidth = null;

    protected ?int $ogImageHeight = null;

    protected ?string $ogImageType = null;

    protected ?string $ogTransform = null;

    protected ?string $ogLocale = null;

    protected ?DateTimeInterface $ogDateUpdated = null;

    protected ?DateTimeInterface $ogDateCreated = null;

    protected ?DateTimeInterface $ogExpiryDate = null;

    public static function displayName(): string
    {
        return Craft::t('sprout-module-meta', 'Open Graph');
    }

    public function attributes(): array
    {
        $attributes = parent::attributes();
        $attributes[] = 'ogType';
        $attributes[] = 'ogSiteName';
        $attributes[] = 'ogPublisher';
        $attributes[] = 'ogAuthor';
        $attributes[] = 'ogUrl';
        $attributes[] = 'ogTitle';
        $attributes[] = 'ogDescription';
        $attributes[] = 'ogImage';
        $attributes[] = 'ogImageSecure';
        $attributes[] = 'ogImageWidth';
        $attributes[] = 'ogImageHeight';
        $attributes[] = 'ogImageType';
        $attributes[] = 'ogTransform';
        $attributes[] = 'ogLocale';
        $attributes[] = 'ogDateCreated';
        $attributes[] = 'ogDateUpdated';
        $attributes[] = 'ogExpiryDate';

        return $attributes;
    }

    public function getAttributesMapping(): array
    {
        return [
            'ogType' => 'og:type',
            'ogSiteName' => 'og:site_name',
            'ogPublisher' => 'article:publisher',
            'ogAuthor' => 'og:author',
            'ogUrl' => 'og:url',
            'ogTitle' => 'og:title',
            'ogDescription' => 'og:description',
            'ogImage' => 'og:image',
            'ogImageSecure' => 'og:image:secure_url',
            'ogImageWidth' => 'og:image:width',
            'ogImageHeight' => 'og:image:height',
            'ogImageType' => 'og:image:type',
            'ogLocale' => 'og:locale',
            'ogDateCreated' => 'article:published_time',
            'ogDateUpdated' => 'article:modified_time',
            'ogExpiryDate' => 'article:expiration_time',
        ];
    }

    public function getHandle(): string
    {
        return 'openGraph';
    }

    public function getIconPath(): string
    {
        return '@Sprout/Assets/dist/static/meta/icons/facebook-f.svg';
    }

    public function getSettingsHtml(Field $field): string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-meta/_components/fields/elementmetadata/blocks/open-graph.twig', [
            'meta' => $this,
            'field' => $field,
        ]);
    }

    public function showMetaDetailsTab(): bool
    {
        return MetaModule::getInstance()->optimizeMetadata->elementMetadataField->showOpenGraph;
    }

    public function getOgType(): ?string
    {
        if ($this->ogType || $this->metadata->getRawDataOnly()) {
            return $this->ogType;
        }

        return MetaModule::getInstance()->optimizeMetadata->globals->getSettings()['defaultOgType'] ?? 'website';
    }

    public function setOgType(?string $value): void
    {
        $this->ogType = $value;
    }

    public function getOgSiteName(): ?string
    {
        if ($this->ogSiteName || $this->metadata->getRawDataOnly()) {
            return $this->ogSiteName;
        }

        return MetaModule::getInstance()->optimizeMetadata->globals->getIdentity()['name'] ?? Craft::$app->getSystemName();
    }

    public function setOgSiteName(?string $value): void
    {
        $this->ogSiteName = $value;
    }

    public function getOgAuthor(): ?string
    {
        if ($this->getOgType() !== 'article' || $this->metadata->getRawDataOnly()) {
            return null;
        }

        return $this->ogAuthor;
    }

    public function setOgAuthor(?string $value): void
    {
        $this->ogAuthor = $value;
    }

    public function getOgPublisher(): ?string
    {
        if ($this->getOgType() !== 'article' || $this->metadata->getRawDataOnly()) {
            return null;
        }

        if ($this->ogPublisher) {
            return $this->ogPublisher;
        }

        $socialSettings = MetaModule::getInstance()->optimizeMetadata->globals->getSocial();
        $facebookPage = $this->getFacebookPage($socialSettings);

        return $facebookPage ?? null;
    }

    public function setOgPublisher(?string $value): void
    {
        $this->ogPublisher = $value;
    }

    public function getOgUrl(): ?string
    {
        if ($this->ogUrl || $this->metadata->getRawDataOnly()) {
            return $this->ogUrl;
        }

        return $this->metadata->getCanonical();
    }

    public function setOgUrl(?string $value): void
    {
        $this->ogUrl = $value;
    }

    public function getOgTitle(): ?string
    {
        if ($this->ogTitle || $this->metadata->getRawDataOnly()) {
            return trim($this->ogTitle);
        }

        if ($optimizedTitle = $this->metadata->getOptimizedTitle()) {
            return trim($optimizedTitle) ?: null;
        }

        $identity = MetaModule::getInstance()->optimizeMetadata->globals->getIdentity();

        return isset($identity['name']) ? trim($identity['name']) : null;
    }

    public function setOgTitle(?string $value): void
    {
        $this->ogTitle = $value;
    }

    public function getOgDescription(): ?string
    {
        $descriptionLength = MetaModule::getInstance()->elementMetadata->getDescriptionLength();

        if ($this->ogDescription || $this->metadata->getRawDataOnly()) {
            return mb_substr($this->ogDescription, 0, $descriptionLength) ?: null;
        }

        if ($optimizedDescription = $this->metadata->getOptimizedDescription()) {
            return mb_substr($optimizedDescription, 0, $descriptionLength) ?: null;
        }

        $globalDescription = MetaModule::getInstance()->optimizeMetadata->globals->getIdentity()['description'] ?? null;

        return mb_substr($globalDescription, 0, $descriptionLength) ?: null;
    }

    public function setOgDescription(?string $value): void
    {
        $this->ogDescription = $value;
    }

    public function getOgImageSecure(): ?string
    {
        if ($this->metadata->getRawDataOnly()) {
            return null;
        }

        return $this->ogImageSecure;
    }

    public function setOgImageSecure(?string $value): void
    {
        $this->ogImageSecure = $value;
    }

    public function getOgImage()
    {
        if ($this->ogImage || $this->metadata->getRawDataOnly()) {
            return $this->ogImage;
        }

        if ($optimizedImage = $this->metadata->getOptimizedImage()) {
            return $this->normalizeImageValue($optimizedImage);
        }

        return MetaModule::getInstance()->optimizeMetadata->globals->getIdentity()['image'] ?? null;
    }

    public function setOgImage(string|array|null $value): void
    {
        $this->ogImage = is_array($value) ? $value[0] ?? null : $value;
    }

    public function getOgImageWidth(): ?int
    {
        return $this->ogImageWidth;
    }

    public function setOgImageWidth(int $value): void
    {
        $this->ogImageWidth = $value;
    }

    public function getOgImageHeight(): ?int
    {
        return $this->ogImageHeight;
    }

    public function setOgImageHeight(?int $value): void
    {
        $this->ogImageHeight = $value;
    }

    public function getOgImageType(): ?string
    {
        if ($this->ogImageType || $this->metadata->getRawDataOnly()) {
            return $this->ogImageType;
        }

        return null;
    }

    public function setOgImageType(?string $value): void
    {
        $this->ogImageType = $value;
    }

    public function getOgTransform(): ?string
    {
        if ($this->ogTransform || $this->metadata->getRawDataOnly()) {
            return $this->ogTransform;
        }

        return MetaModule::getInstance()->optimizeMetadata->globals->getSettings()['ogTransform'] ?? null;
    }

    public function setOgTransform(?string $value): void
    {
        $this->ogTransform = $value;
    }

    public function getOgLocale(): ?string
    {
        if ($this->ogLocale || $this->metadata->getRawDataOnly()) {
            return $this->ogLocale;
        }

        $site = Craft::$app->sites->getCurrentSite();

        return $site->language ?? null;
    }

    public function setOgLocale(?string $value): void
    {
        $this->ogLocale = $value;
    }

    public function getOgDateUpdated(): DateTimeInterface|string|null
    {
        if ($this->getOgType() !== 'article' || $this->metadata->getRawDataOnly()) {
            return null;
        }

        $element = MetaModule::getInstance()->optimizeMetadata->element;

        if ($element !== null) {
            $dateUpdated = $element->dateUpdated ?? null;

            if ($dateUpdated !== null) {
                return $dateUpdated->format(DateTime::ATOM);
            }
        }

        return $this->ogDateUpdated;
    }

    public function setOgDateUpdated(?DateTimeInterface $value): void
    {
        $this->ogDateUpdated = $value;
    }

    public function getOgDateCreated()
    {
        if ($this->getOgType() !== 'article' || $this->metadata->getRawDataOnly()) {
            return null;
        }

        $element = MetaModule::getInstance()->optimizeMetadata->element;

        if ($element !== null) {
            $postDate = $element->postDate ?? null;

            if ($postDate) {
                return $postDate->format(DateTime::ATOM);
            }

            $dateUpdated = $element->dateUpdated ?? null;

            if ($dateUpdated !== null) {
                return $dateUpdated->format(DateTime::ATOM);
            }
        }

        return $this->ogDateCreated;
    }

    public function setOgDateCreated(?DateTimeInterface $value): void
    {
        $this->ogDateCreated = $value;
    }

    public function getOgExpiryDate()
    {
        if ($this->getOgType() !== 'article' || $this->metadata->getRawDataOnly()) {
            return null;
        }

        $element = MetaModule::getInstance()->optimizeMetadata->element;

        if ($element !== null) {
            $expiryDate = $element->expiryDate ?? null;

            if ($expiryDate) {
                return $expiryDate->format(DateTime::ATOM);
            }
        }

        return $this->ogExpiryDate;
    }

    public function setOgExpiryDate(?DateTimeInterface $value): void
    {
        $this->ogExpiryDate = $value;
    }

    public function getMetaTagData(): array
    {
        $tagData = parent::getMetaTagData();

        // If the value that exists is not a URL, we need to process it
        if (isset($tagData['og:image']) &&
            mb_strpos($tagData['og:image'], 'http') !== 0) {
            [
                $tagData['og:image'],
                $tagData['og:image:width'],
                $tagData['og:image:height'],
                $tagData['og:image:type'],
            ] = $this->prepareAssetMetaData($tagData['og:image'], $this->getOgTransform(), false);

            $tagData['og:image:secure_url'] = $tagData['og:image'];
        }

        return array_filter($tagData);
    }

    /**
     * Returns the first Facebook Page found in the Social Profile settings
     */
    public function getFacebookPage($socialProfiles = null): ?string
    {
        if ($socialProfiles === null) {
            return null;
        }

        $facebookUrl = null;

        foreach ($socialProfiles as $profile) {
            $socialProfileNameFromPost = $profile[0] ?? null;
            $socialProfileNameFromSettings = $profile['profileName'] ?? null;

            // Support syntax for both POST data being saved and previous saved social settings
            if ($socialProfileNameFromPost === 'Facebook' || $socialProfileNameFromSettings === 'Facebook') {
                $facebookUrlFromPost = isset($socialProfileNameFromPost) ? $profile[1] : null;
                $facebookUrl = $socialProfileNameFromSettings !== null ? $profile['url'] : $facebookUrlFromPost;

                break;
            }
        }

        return $facebookUrl;
    }
}
