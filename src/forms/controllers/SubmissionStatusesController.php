<?php

namespace BarrelStrength\Sprout\forms\controllers;

use BarrelStrength\Sprout\forms\FormsModule;
use BarrelStrength\Sprout\forms\submissions\SubmissionStatus;
use Craft;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\web\Controller as BaseController;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class SubmissionStatusesController extends BaseController
{
    public function actionEdit(int $submissionStatusId = null, SubmissionStatus $submissionStatus = null): Response
    {
        $this->requireAdmin(false);

        if ($submissionStatus === null) {
            if ($submissionStatusId) {
                $submissionStatus = FormsModule::getInstance()->submissionStatuses->getSubmissionStatusById($submissionStatusId);

                if (!$submissionStatus->id) {
                    throw new NotFoundHttpException('Submission Status not found');
                }

                if ($submissionStatus->handle == SubmissionStatus::SPAM_STATUS_HANDLE) {

                    Craft::$app->session->setError(Craft::t('sprout-module-forms', "Spam status can't be updated"));

                    return $this->redirect(UrlHelper::cpUrl('sprout/settings/forms/submission-statuses'));
                }
            } else {
                $submissionStatus = new SubmissionStatus();
            }
        }

        return $this->renderTemplate('sprout-module-forms/_settings/submission-statuses/edit', [
            'submissionStatus' => $submissionStatus,
            'submissionStatusId' => $submissionStatusId,
        ]);
    }

    public function actionSave(): ?Response
    {
        $this->requirePostRequest();
        $this->requireAdmin(false);

        $id = Craft::$app->request->getBodyParam('submissionStatusId');
        $submissionStatus = FormsModule::getInstance()->submissionStatuses->getSubmissionStatusById($id);

        $submissionStatus->name = Craft::$app->request->getBodyParam('name');
        $submissionStatus->handle = Craft::$app->request->getBodyParam('handle');
        $submissionStatus->color = Craft::$app->request->getBodyParam('color');
        $submissionStatus->isDefault = (bool)Craft::$app->request->getBodyParam('isDefault');

        if (empty($submissionStatus->isDefault)) {
            $submissionStatus->isDefault = 0;
        }

        if (!FormsModule::getInstance()->submissionStatuses->saveSubmissionStatus($submissionStatus)) {
            Craft::$app->session->setError(Craft::t('sprout-module-forms', 'Could not save Submission Status.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'submissionStatus' => $submissionStatus,
            ]);

            return null;
        }

        Craft::$app->session->setNotice(Craft::t('sprout-module-forms', 'Submission Status saved.'));

        return $this->redirectToPostedUrl();
    }

    public function actionReorder(): Response
    {
        $this->requirePostRequest();
        $this->requireAdmin(false);

        $ids = Json::decode(Craft::$app->request->getRequiredBodyParam('ids'));

        if (FormsModule::getInstance()->submissionStatuses->reorderSubmissionStatuses($ids)) {
            return $this->asJson([
                'success' => true
            ]);
        }

        return $this->asJson(['error' => Craft::t('sprout-module-forms', "Couldn't reorder Order Statuses.")]);
    }

    public function actionDelete(): Response
    {
        $this->requirePostRequest();
        $this->requireAdmin(false);

        $submissionStatusId = Craft::$app->request->getRequiredBodyParam('id');

        if (!FormsModule::getInstance()->submissionStatuses->deleteSubmissionStatusById($submissionStatusId)) {
            return $this->asJson(['success' => false]);
        }

        return $this->asJson(['success' => true]);
    }
}
