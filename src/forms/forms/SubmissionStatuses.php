<?php

namespace BarrelStrength\Sprout\forms\forms;

use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use BarrelStrength\Sprout\forms\db\SproutTable;
use BarrelStrength\Sprout\forms\FormsModule;
use BarrelStrength\Sprout\forms\submissions\SubmissionStatus;
use BarrelStrength\Sprout\forms\submissions\SubmissionStatusRecord;
use Craft;
use yii\base\Component;
use yii\base\Exception;
use yii\db\Transaction;

class SubmissionStatuses extends Component
{
    /**
     * @return SubmissionStatus[]
     */
    public function getAllSubmissionStatuses(): array
    {
        $results = SubmissionStatusRecord::find()
            ->orderBy(['sortOrder' => 'asc'])
            ->all();

        $submissionStatuses = [];

        foreach ($results as $result) {
            $submissionStatuses[] = new SubmissionStatus($result->getAttributes());
        }

        return $submissionStatuses;
    }

    public function getSubmissionStatusById($submissionStatusId): SubmissionStatus
    {
        $submissionStatus = SubmissionStatusRecord::find()
            ->where(['id' => $submissionStatusId])
            ->one();

        $submissionStatusesModel = new SubmissionStatus();

        if ($submissionStatus) {
            $submissionStatusesModel->setAttributes($submissionStatus->getAttributes(), false);
        }

        return $submissionStatusesModel;
    }

    public function getSubmissionStatusByHandle($submissionStatusHandle): SubmissionStatus
    {
        $submissionStatus = SubmissionStatusRecord::find()
            ->where(['handle' => $submissionStatusHandle])
            ->one();

        $submissionStatusesModel = new SubmissionStatus();

        if ($submissionStatus) {
            $submissionStatusesModel->setAttributes($submissionStatus->getAttributes(), false);
        }

        return $submissionStatusesModel;
    }

    public function saveSubmissionStatus(SubmissionStatus $submissionStatus): bool
    {
        $isNew = !$submissionStatus->id;

        $record = new SubmissionStatusRecord();

        if ($submissionStatus->id) {
            $record = SubmissionStatusRecord::findOne($submissionStatus->id);

            if (!$record instanceof SubmissionStatusRecord) {
                throw new Exception('No Submission Status exists with the ID: ' . $submissionStatus->id);
            }
        }

        $attributes = $submissionStatus->getAttributes();

        if ($isNew) {
            unset($attributes['id']);
        }

        $record->setAttributes($attributes, false);

        $record->sortOrder = $submissionStatus->sortOrder ?: 999;

        $submissionStatus->validate();

        if (!$submissionStatus->hasErrors()) {

            /** @var Transaction $transaction */
            $transaction = Craft::$app->db->beginTransaction();

            try {
                if ($record->isDefault) {
                    SubmissionStatusRecord::updateAll(['isDefault' => false]);
                }

                $record->save(false);

                if (!$submissionStatus->id) {
                    $submissionStatus->id = $record->id;
                }

                $transaction->commit();
            } catch (Exception $exception) {
                $transaction->rollBack();
                throw $exception;
            }

            return true;
        }

        return false;
    }

    public function deleteSubmissionStatusById($id): bool
    {
        $hasStatus = SubmissionElement::find()->where(['statusId' => $id])->exists();

        if ($hasStatus) {
            return false;
        }

        $submissionStatus = SubmissionStatusRecord::findOne($id);

        if (!$submissionStatus || $submissionStatus->isDefault || $submissionStatus->handle === 'spam') {
            return false;
        }

        $submissionStatus->delete();

        return true;
    }

    /**
     * Reorders Submission Statuses
     */
    public function reorderSubmissionStatuses($submissionStatusIds): bool
    {
        /** @var Transaction $transaction */
        $transaction = Craft::$app->db->beginTransaction();

        try {
            foreach ($submissionStatusIds as $submissionStatus => $submissionStatusId) {
                $submissionStatusRecord = $this->getSubmissionStatusRecordById($submissionStatusId);
                $submissionStatusRecord->sortOrder = $submissionStatus + 1;
                $submissionStatusRecord->save();
            }

            $transaction->commit();
        } catch (Exception $exception) {
            $transaction->rollBack();

            throw $exception;
        }

        return true;
    }

    /**
     * Assume this exists. User cannot delete the final status.
     */
    public function getDefaultSubmissionStatus(): SubmissionStatus
    {
        /** @var SubmissionStatusRecord $submissionStatus */
        $submissionStatus = SubmissionStatusRecord::find()
            ->orderBy(['isDefault' => SORT_DESC])
            ->one();

        if (!$submissionStatus) {
            $submissionStatus = new SubmissionStatusRecord();
            $submissionStatus->name = 'Unread';
            $submissionStatus->handle = 'unread';
            $submissionStatus->color = 'blue';
            $submissionStatus->isDefault = true;
            $submissionStatus->save();

            return $this->getDefaultSubmissionStatus();
        }

        return new SubmissionStatus($submissionStatus->getAttributes());
    }

    public function getSpamStatusId(): ?int
    {
        $spam = FormsModule::getInstance()->submissionStatuses->getSubmissionStatusByHandle(SubmissionStatus::SPAM_STATUS_HANDLE);

        if (!$spam->id) {
            return null;
        }

        return $spam->id;
    }

    /**
     * Mark submissions as Spam
     */
    public function markAsSpam($submissionElements): bool
    {
        $spam = FormsModule::getInstance()->submissionStatuses->getSubmissionStatusByHandle(SubmissionStatus::SPAM_STATUS_HANDLE);

        if (!$spam->id) {
            return false;
        }

        foreach ($submissionElements as $submissionElement) {

            $success = Craft::$app->db->createCommand()->update(
                SproutTable::FORM_SUBMISSIONS,
                ['statusId' => $spam->id],
                ['id' => $submissionElement->id]
            )->execute();

            if (!$success) {
                Craft::error("Unable to mark submission as spam. Submission ID: {$submissionElement->id}", __METHOD__);
            }
        }

        return true;
    }

    /**
     * Mark submissions as Not Spam
     */
    public function markAsDefaultStatus($submissionElements): bool
    {
        /** @var SubmissionStatus $defaultStatus */
        $defaultStatus = $this->getDefaultSubmissionStatus();

        foreach ($submissionElements as $submissionElement) {
            $success = Craft::$app->db->createCommand()->update(
                SproutTable::FORM_SUBMISSIONS,
                ['statusId' => $defaultStatus->id],
                ['id' => $submissionElement->id]
            )->execute();

            if (!$success) {
                Craft::error("Unable to change submission status. Submission ID: {$submissionElement->id}", __METHOD__);
            }
        }

        return true;
    }

    private function getSubmissionStatusRecordById($submissionStatusId = null): SubmissionStatusRecord
    {
        if ($submissionStatusId) {
            $submissionStatusRecord = SubmissionStatusRecord::findOne($submissionStatusId);

            if (!$submissionStatusRecord instanceof SubmissionStatusRecord) {
                throw new Exception('No Submission Status exists with the ID: ' . $submissionStatusId);
            }
        }

        return $submissionStatusRecord ?? new SubmissionStatusRecord();
    }
}
