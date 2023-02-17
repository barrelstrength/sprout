<?php

namespace BarrelStrength\Sprout\forms\integrations;

use craft\base\SavableComponentInterface;

/**
 * IntegrationInterface defines the common interface to be implemented by Integration classes.
 * A class implementing this interface should also use [[SavableComponentTrait]] and [[IntegrationTrait]].
 */
interface IntegrationInterface extends SavableComponentInterface
{
    /**
     * Message to display when the submit() action is successful
     */
    public function getSuccessMessage(): ?string;

    /**
     * Prepare and send the submission to the desired endpoint
     */
    public function submit(): bool;

    /**
     * Returns an array of fields to be used for the dropdown of each row of the mapping.
     * Integrations will display a plain text field by default.
     *
     * @example
     *       return [
     *       0 => [
     *       0 => [
     *       'label' => 'Title',
     *       'value' => 'title'
     *       ],
     *       1 => [
     *       'label' => 'Slug',
     *       'value' => 'slug'
     *       ]
     *       ],
     *       1 => [
     *       0 => [
     *       'label' => 'Title',
     *       'value' => 'title'
     *       ],
     *       1 => [
     *       'label' => 'Slug',
     *       'value' => 'slug'
     *       ]
     *       ]
     *       ];
     *
     */
    public function getTargetIntegrationFieldsAsMappingOptions(): array;

    /**
     * Returns an array that represents the Target Integration field values
     *
     * The $this->fieldMapping property will be populated from the values
     * saved via the settings defined in an Integrations
     * $this->getFieldMappingSettingsHtml() method
     *
     * [
     *   'title' => 'Title of Submission',
     *   'customTargetFieldHandle' => 'Value of Custom Field'
     * ]
     */
    public function getTargetIntegrationFieldValues(): array;
}
