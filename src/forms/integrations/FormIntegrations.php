<?php

namespace BarrelStrength\Sprout\forms\integrations;

use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use BarrelStrength\Sprout\forms\components\events\OnAfterIntegrationSubmitEvent;
use BarrelStrength\Sprout\forms\components\events\OnSaveSubmissionEvent;
use BarrelStrength\Sprout\forms\components\integrationtypes\MissingIntegrationType;
use BarrelStrength\Sprout\forms\db\SproutTable;
use BarrelStrength\Sprout\forms\FormsModule;
use BarrelStrength\Sprout\forms\submissions\SubmissionIntegrationStatus;
use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\errors\MissingComponentException;
use craft\events\RegisterComponentTypesEvent;
use craft\helpers\Component as ComponentHelper;
use yii\base\Exception;

class FormIntegrations extends Component
{
    public const EVENT_REGISTER_INTEGRATIONS = 'registerSproutFormIntegrations';

    /**
     * @event OnAfterIntegrationSubmit The event that is triggered when the integration is submitted
     */
    public const EVENT_AFTER_INTEGRATION_SUBMIT = 'onAfterIntegrationSubmit';

    /**
     * Returns all registered Integration Types
     */
    public function getAllIntegrationTypes(): array
    {
        $event = new RegisterComponentTypesEvent([
            'types' => [],
        ]);

        $this->trigger(self::EVENT_REGISTER_INTEGRATIONS, $event);

        return $event->types;
    }

    /**
     * @return Integration[]
     */
    public function getAllIntegrations(): array
    {
        $integrationTypes = FormsModule::getInstance()->formIntegrations->getAllIntegrationTypes();

        $integrations = [];

        foreach ($integrationTypes as $integrationType) {
            $integrations[] = new $integrationType();
        }

        return $integrations;
    }

    public function getIntegrationsByFormId($formId): array
    {
        $results = (new Query())
            ->select([
                'integrations.id',
                'integrations.formId',
                'integrations.name',
                'integrations.type',
                'integrations.sendRule',
                'integrations.settings',
                'integrations.enabled',
            ])
            ->from(['integrations' => SproutTable::FORM_INTEGRATIONS])
            ->where(['integrations.formId' => $formId])
            ->all();

        $integrations = [];

        foreach ($results as $result) {
            $integration = ComponentHelper::createComponent($result, IntegrationInterface::class);
            $integrations[] = $integration;
        }

        return $integrations;
    }

    public function getIntegrationById($integrationId): ?Integration
    {
        $result = (new Query())
            ->select([
                'integrations.id',
                'integrations.formId',
                'integrations.name',
                'integrations.type',
                'integrations.sendRule',
                'integrations.settings',
                'integrations.enabled',
            ])
            ->from(['integrations' => SproutTable::FORM_INTEGRATIONS])
            ->where(['integrations.id' => $integrationId])
            ->one();

        if (!$result) {
            return null;
        }

        $integration = ComponentHelper::createComponent($result, IntegrationInterface::class);

        return new $result['type']($integration);
    }

    public function saveIntegration(Integration $integration): bool
    {
        $integrationRecord = IntegrationRecord::findOne($integration->id);

        if ($integrationRecord === null) {
            $integrationRecord = new IntegrationRecord();
        }

        $integrationRecord->type = $integration::class;
        $integrationRecord->formId = $integration->formId;
        $integrationRecord->name = $integration->name ?? $integration::displayName();
        $integrationRecord->enabled = $integration->enabled;
        $integrationRecord->sendRule = $integration->sendRule;

        $integrationRecord->settings = $integration->getSettings();

        if ($integrationRecord->save()) {
            $integration->id = $integrationRecord->id;
            $integration->name = $integrationRecord->name;

            return true;
        }

        return false;
    }

    public function createIntegration($config): Integration
    {
        if (is_string($config)) {
            $config = ['type' => $config];
        }

        try {
            /** @var Integration $integration */
            $integration = ComponentHelper::createComponent($config, IntegrationInterface::class);
        } catch (MissingComponentException $missingComponentException) {
            $config['errorMessage'] = $missingComponentException->getMessage();
            $config['expectedType'] = $config['type'];
            unset($config['type']);

            $integration = new MissingIntegrationType($config);
        }

        return $integration;
    }

    /**
     * Loads the sprout modal integration via ajax.
     */
    public function getModalIntegrationTemplate(Integration $integration): array
    {
        $view = Craft::$app->getView();

        $html = $view->renderTemplate('sprout-module-forms/forms/_editIntegrationModal', [
            'integration' => $integration,
        ]);

        $js = $view->getBodyHtml();
        $css = $view->getHeadHtml();

        return [
            'html' => $html,
            'js' => $js,
            'css' => $css,
        ];
    }

    public function logIntegration(IntegrationLog $integrationLog): IntegrationLog
    {
        $integrationLogRecord = new IntegrationLogRecord();
        if ($integrationLog->id) {
            $integrationLogRecord = IntegrationLogRecord::findOne($integrationLog->id);
            if (!$integrationLogRecord instanceof IntegrationLogRecord) {
                throw new Exception('No integration log record exists with id ' . $integrationLog->id);
            }
        }

        $integrationLogRecord->submissionId = $integrationLog->submissionId;
        $integrationLogRecord->integrationId = $integrationLog->integrationId;
        $integrationLogRecord->success = $integrationLog->success;
        if (is_array($integrationLog->message)) {
            $integrationLog->message = json_encode($integrationLog->message, JSON_THROW_ON_ERROR);
        }

        $integrationLogRecord->message = $integrationLog->message;
        $integrationLogRecord->status = $integrationLog->status;
        $integrationLogRecord->save();

        $integrationLog->setAttributes($integrationLogRecord->getAttributes(), false);

        return $integrationLog;
    }

    public function getIntegrationLogsBySubmissionId($submissionId): array
    {
        return (new Query())
            ->select(['*'])
            ->from([SproutTable::FORM_INTEGRATIONS_LOG])
            ->where(['submissionId' => $submissionId])
            ->all();
    }

    public function handleFormIntegrations(OnSaveSubmissionEvent $event): void
    {
        $this->runFormIntegrations($event->submission);
    }

    /**
     * Run all the integrations related to the Form Element.
     */
    public function runFormIntegrations(SubmissionElement $submission): void
    {
        if ($submission->hasCaptchaErrors()) {
            return;
        }

        $form = $submission->getForm();
        $integrations = $this->getIntegrationsByFormId($form->getId());

        if (!Craft::$app->getRequest()->getIsSiteRequest() || empty($integrations)) {
            return;
        }

        $integrationLogs = [];
        $submissionId = $submission->getId();

        // Add all enabled Integrations to the log as 'Pending'
        foreach ($integrations as $integration) {
            if ($integration->enabled) {
                $integrationLog = new IntegrationLog();

                $integrationLog->setAttributes([
                    'integrationId' => $integration->id,
                    'submissionId' => $submissionId,
                    'success' => false,
                    'status' => SubmissionIntegrationStatus::SUBMISSION_INTEGRATION_PENDING_STATUS,
                    'message' => 'Pending',
                ], false);

                $integrationLog = FormsModule::getInstance()->formIntegrations->logIntegration($integrationLog);

                $integrationLogs[] = [
                    'integration' => $integration,
                    'integrationLog' => $integrationLog,
                ];
            }
        }

        // Process and Send Integrations one by one
        foreach ($integrationLogs as $integrationLog) {
            /** @var Integration $integration */
            $integration = $integrationLog['integration'];
            /** @var IntegrationLog $integrationLog */
            $integrationLog = $integrationLog['integrationLog'];

            $integration->submission = $submission;

            Craft::info('Running Integration: ' . $integration->name . ' for Submission ' . $submissionId, __METHOD__);

            if (!$this->sendRuleIsTrue($integration, $submission)) {
                $integrationNotSentMessage = Craft::t('sprout-module-forms', 'Integration not sent. Send Rule setting did not evaluate to true.');

                Craft::info($integrationNotSentMessage, __METHOD__);

                $integrationLog->setAttributes([
                    'success' => true,
                    'status' => SubmissionIntegrationStatus::SUBMISSION_INTEGRATION_NOT_SENT_STATUS,
                    'message' => $integrationNotSentMessage,
                ], false);

                FormsModule::getInstance()->formIntegrations->logIntegration($integrationLog);

                continue;
            }

            try {
                if ($integration->enabled) {
                    $result = $integration->submit();
                    // Success!
                    if ($result) {
                        $integrationLog->setAttributes([
                            'success' => true,
                            'status' => SubmissionIntegrationStatus::SUBMISSION_INTEGRATION_COMPLETED_STATUS,
                            'message' => $integration->getSuccessMessage(),
                        ], false);

                        $integrationLog = FormsModule::getInstance()->formIntegrations->logIntegration($integrationLog);
                    }
                }
            } catch (\Exception $exception) {
                $message = Craft::t('sprout-module-forms', 'Integration failed to submit: {message}', [
                    'message' => $exception->getMessage(),
                ]);
                $integration->addError('global', $message);
                Craft::error($message, __METHOD__);
            }

            $integrationErrors = $integration->getErrors();
            // Let's log errors
            if ($integrationErrors) {
                $errorMessages = [];
                foreach ($integrationErrors as $integrationError) {
                    $errorMessages[] = $integrationError;
                }

                $integrationLog->setAttributes([
                    'success' => false,
                    'message' => $errorMessages,
                    'status' => SubmissionIntegrationStatus::SUBMISSION_INTEGRATION_COMPLETED_STATUS,
                ], false);

                $integrationLog = FormsModule::getInstance()->formIntegrations->logIntegration($integrationLog
                );
            }

            $event = new OnAfterIntegrationSubmitEvent([
                'integrationLog' => $integrationLog,
            ]);

            $this->trigger(self::EVENT_AFTER_INTEGRATION_SUBMIT, $event);
        }
    }

    public function getIntegrationOptions(): array
    {
        $options = [];
        $integrations = FormsModule::getInstance()->formIntegrations->getAllIntegrations();

        $options[] = [
            'label' => Craft::t('sprout-module-forms', 'Add Integration...'),
            'value' => '',
        ];

        foreach ($integrations as $integration) {
            $options[] = [
                'label' => $integration::displayName(),
                'value' => $integration::class,
            ];
        }

        return $options;
    }

    private function sendRuleIsTrue(Integration $integration, $submission): bool
    {
        // Default setting: Always = *
        if ($integration->sendRule === '*') {
            return true;
        }

        // If the rule name matches an Opt-in field handle, see if the Opt-in field is checked
        if (isset($submission->{$integration->sendRule}) && $submission->{$integration->sendRule}) {
            return true;
        }

        // Custom Send Rule
        try {
            $resultTemplate = Craft::$app->view->renderObjectTemplate($integration->sendRule, $submission);
            $value = trim($resultTemplate);
            if (filter_var($value, FILTER_VALIDATE_BOOLEAN)) {
                return true;
            }
        } catch (\Exception $exception) {
            Craft::error($exception->getMessage(), __METHOD__);
        }

        return false;
    }
}
