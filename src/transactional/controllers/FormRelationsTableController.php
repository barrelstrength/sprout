<?php

namespace BarrelStrength\Sprout\transactional\controllers;

use BarrelStrength\Sprout\transactional\components\formfeatures\TransactionalFormFeature;
use Craft;
use craft\errors\ElementNotFoundException;
use craft\web\Controller;
use yii\web\Response;

class FormRelationsTableController extends Controller
{
    public function actionGetRelationsTable(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $elementId = Craft::$app->getRequest()->getRequiredParam('elementId');

        $element = Craft::$app->getElements()->getElementById($elementId);

        if (!$element) {
            throw new ElementNotFoundException('Unable to find related form.');
        }

        return $this->asJson([
            'success' => true,
            'html' => TransactionalFormFeature::getRelationsTableField($element)->formHtml(),
        ]);
    }
}
