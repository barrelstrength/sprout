<?php

namespace BarrelStrength\Sprout\meta\metadata;

/**
 * @todo - review. May no longer be in use.
 *
 * Metadata Levels are used to establish which metadata gets priority
 */
abstract class MetadataLevel
{
    /**
     * Global Metadata
     *
     * UI Names: Globals, Global Metadata
     * Internal Names: GlobalMetadata, globalMetadataModel
     * Priority: 3, Lowest Priority
     */
    public const GlobalMetadata = 'global';

    /**
     * Element Metadata
     *
     * UI Names: Pages, Element Metadata
     * Internal Names: ElementMetadata, $elementMetadataModel
     * Priority: 1
     */
    public const ElementMetadata = 'element';

    /**
     * UI Names: Template Metadata, Template Overrides, Code Overrides
     * Internal Names: TemplateMetadata, $templateMetadataModel
     * Priority: 0, Highest Priority
     */
    public const TemplateMetadata = 'template';
}
