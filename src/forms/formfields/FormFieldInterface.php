<?php

namespace BarrelStrength\Sprout\forms\formfields;

use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;

interface FormFieldInterface
{
    /**
     * The example HTML input field that displays in the UI when a field is dragged to the form layout editor
     */
    public function getExampleInputHtml(): string;

    public function getFrontEndInputVariables($value, SubmissionElement $submission, array $renderingOptions = null): array;

    /**
     * The HTML to render when a Form is output using the displayForm, displayTab, or displayField tags
     */
    //public function getFrontEndInputHtml($value, SubmissionElement $submission, array $renderingOptions = null): Markup;
}
