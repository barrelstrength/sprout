<?php

namespace BarrelStrength\Sprout\core\controllers\web;

use BarrelStrength\Sprout\core\relations\RelationsHelper;
use BarrelStrength\Sprout\transactional\components\elements\TransactionalEmailElement;
use Craft;
use craft\web\Controller;
use yii\web\Response;

class SourceElementRelationsController extends Controller
{
    public function actionGetRelations(): ?Response
    {
        $this->requirePostRequest();

        $elementId = Craft::$app->getRequest()->getBodyParam('elementId');

        $relations = RelationsHelper::getElementRelationsById($elementId, [
            TransactionalEmailElement::class,
        ]);

        $relationsModalHtml = Craft::$app->getView()->renderTemplate('sprout-module-core/_components/relations/modal', [
            'elementId' => $this->id,
            'relations' => $relations,
        ]);

        return $this->asJson([
            'success' => true,
            'html' => $relationsModalHtml,
        ]);
    }
}
