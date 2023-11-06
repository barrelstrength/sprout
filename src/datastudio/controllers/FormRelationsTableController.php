<?php

namespace BarrelStrength\Sprout\datastudio\controllers;

use BarrelStrength\Sprout\forms\components\elements\FormElement;
use Craft;
use craft\web\Controller;
use yii\web\Response;

class FormRelationsTableController extends Controller
{
    public function actionGetRelationsTable(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $elementId = Craft::$app->getRequest()->getRequiredParam('elementId');

        /** @var FormElement $element */
        $element = Craft::$app->getElements()->getElementById($elementId);

        return $this->asJson([
            'success' => true,
            'html' => $element->getDataSourceRelationsTableField()->formHtml(),
        ]);
    }
}
