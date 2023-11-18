<?php

namespace BarrelStrength\Sprout\meta\components\schema;

use BarrelStrength\Sprout\meta\components\meta\OpenGraphMetaType;
use BarrelStrength\Sprout\meta\schema\Schema;

class ImageObjectSchema extends Schema
{
    public function getName(): string
    {
        return 'Image Object';
    }

    public function getType(): string
    {
        return 'ImageObject';
    }

    public function isUnlistedSchemaType(): bool
    {
        return true;
    }

    public function addProperties(): void
    {
        $image = $this->element;

        if (!$image || !is_array($image)) {
            return;
        }

        $height = $image['height'] ?? null;
        $width = $image['width'] ?? null;

        $this->addUrl('url', $image['url']);
        $this->addNumber('height', $height);
        $this->addNumber('width', $width);

        $prioritizedMetadataModel = $this->prioritizedMetadataModel;

        if ($prioritizedMetadataModel) {
            $openGraphMetaType = $prioritizedMetadataModel->getMetaTypes('openGraph');

            if (!empty($openGraphMetaType)) {
                /** @var OpenGraphMetaType $openGraphMetaType */
                if ($openGraphMetaType->getOgImage()) {
                    $this->addUrl('url', $openGraphMetaType->getOgImage());
                }

                if ($openGraphMetaType->getOgImageHeight()) {
                    $this->addNumber('height', $openGraphMetaType->getOgImageHeight());
                }

                if ($openGraphMetaType->getOgImageWidth()) {
                    $this->addNumber('width', $openGraphMetaType->getOgImageWidth());
                }
            }
        }
    }
}
