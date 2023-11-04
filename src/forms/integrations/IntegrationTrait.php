<?php

namespace BarrelStrength\Sprout\forms\integrations;

use BarrelStrength\Sprout\forms\components\elements\FormElement;
use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;

/**
 * IntegrationTrait implements the common methods and properties for Integration classes.
 */
trait IntegrationTrait
{
    public ?string $name = null;

    /**
     * Whether this Integration will be processed when a form is submitted
     */
    public bool $enabled = true;

    /**
     * The ID of the Form where an Integration exists
     */
    public ?int $formId = null;

    /**
     * The Form Element associated with an Integration
     */
    public ?FormElement $form = null;

    /**
     * The Submission Element associated with an Integration
     */
    public ?SubmissionElement $submission = null;

    /**
     * The Field Mapping settings
     *
     * This data is saved to the database as JSON in the settings column and populated
     * as an array when an Integration Component is created
     *
     * [
     *   [
     *     'sourceFormField' => 'title',
     *     'targetIntegrationField' => 'title'
     *   ],
     *   [
     *     'sourceFormField' => 'customFormFieldHandle',
     *     'targetIntegrationField' => 'customTargetFieldHandle'
     *   ]
     * ]
     */
    public ?array $fieldMapping = null;

    /**
     * Statement that gets evaluated to true/false to determine this Integration will be submitted
     */
    public bool $sendRule = false;

    public ?array $conditionRules = null;

    public ?string $uid = null;
}
