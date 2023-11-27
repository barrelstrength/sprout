<?php

namespace BarrelStrength\Sprout\forms\submissions;

use BarrelStrength\Sprout\forms\components\elements\db\SubmissionElementQuery;
use BarrelStrength\Sprout\forms\components\elements\SubmissionElement;
use BarrelStrength\Sprout\forms\FormsModule;
use Craft;
use craft\base\ElementInterface;
use craft\errors\ElementNotFoundException;
use craft\queue\BaseJob;
use Exception;

class ResaveSubmissionsJob extends BaseJob
{
    /**
     * The Form ID of the submissions to be saved
     */
    public int $formId;

    /**
     * The element criteria that determines which elements should be re-saved
     */
    public ?array $criteria = null;

    public function execute($queue): void
    {
        // Let's save ourselves some trouble and just clear all the caches for this element class
        Craft::$app->getElements()->invalidateCachesForElementType(SubmissionElement::class);

        /** @var SubmissionElementQuery $query */
        $query = $this->_query();
        $total = $query->count();
        $count = 0;
        $elementsService = Craft::$app->getElements();
        $form = FormsModule::getInstance()->forms->getFormById($this->formId);

        if (!$form instanceof ElementInterface) {
            throw new ElementNotFoundException('No Form exists with id ' . $this->formId);
        }

        foreach ($query->each() as $submission) {
            try {
                $count++;
                $submission->title = Craft::$app->getView()->renderObjectTemplate($form->titleFormat, $submission);
                $submission->resaving = true;
                $elementsService->saveElement($submission);
                $this->setProgress($queue, ($count - 1) / $total, Craft::t('app', '{step} of {total}', [
                    'step' => $count,
                    'total' => $total,
                ]));
            } catch (Exception $exception) {
                Craft::error('Title format error: ' . $exception->getMessage(), __METHOD__);
            }
        }
    }

    protected function defaultDescription(): ?string
    {
        return Craft::t('app', 'Resaving Submissions');
    }

    /**
     * Returns the element query based on the criteria.
     */
    private function _query(): SubmissionElementQuery
    {
        $query = SubmissionElement::find();

        if (!empty($this->criteria)) {
            Craft::configure($query, $this->criteria);
        }

        $query->formId = $this->formId;

        $query
            ->offset(null)
            ->limit(null)
            ->orderBy(null);

        return $query;
    }
}
