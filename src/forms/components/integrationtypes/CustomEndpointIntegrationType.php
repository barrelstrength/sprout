<?php

namespace BarrelStrength\Sprout\forms\components\integrationtypes;

use BarrelStrength\Sprout\forms\integrations\Integration;
use Craft;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class CustomEndpointIntegrationType extends Integration
{
    /**
     * The URL to use when submitting the Form payload
     */
    public ?string $submitAction = null;

    public static function displayName(): string
    {
        return Craft::t('sprout-module-forms', 'Custom Endpoint');
    }

    public function getWorkflowSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/integrationtypes/CustomEndpoint/workflow',
            [
                'integration' => $this,
            ]
        );
    }

    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('sprout-module-forms/_components/integrationtypes/CustomEndpoint/settings',
            [
                'integration' => $this,
            ]
        );
    }

    public function submit(): bool
    {
        if ($this->submitAction == '' || Craft::$app->getRequest()->getIsCpRequest()) {
            return false;
        }

        $submission = $this->submission;
        $targetIntegrationFieldValues = $this->getTargetIntegrationFieldValues();
        $endpoint = $this->submitAction;

        if (!filter_var($endpoint, FILTER_VALIDATE_URL)) {
            $message = $submission->formName . ' submit action is an invalid URL: ' . $endpoint;
            $this->addError('global', $message);
            Craft::error($message, __METHOD__);

            return false;
        }

        $client = new Client();

        Craft::info($targetIntegrationFieldValues, __METHOD__);

        $response = $client->post($endpoint, [
            RequestOptions::JSON => $targetIntegrationFieldValues,
        ]);

        $res = $response->getBody()->getContents();
        $resAsString = is_array($res) ? json_encode($res, JSON_THROW_ON_ERROR) : $res;
        $this->successMessage = $resAsString;
        Craft::info($res, __METHOD__);

        return true;
    }
}
