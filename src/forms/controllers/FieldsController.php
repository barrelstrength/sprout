<?php

namespace BarrelStrength\Sprout\forms\controllers;

use BarrelStrength\Sprout\forms\FormsModule;
use Craft;
use craft\base\Field;
use craft\web\Controller as BaseController;
use yii\web\Response;

class FieldsController extends BaseController
{
    public function actionValidateEmail(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $value = Craft::$app->getRequest()->getParam('value');
        $field = $this->getFieldModel();

        $isValid = FormsModule::getInstance()->emailField->validateEmail($value, $field);

        return $this->asJson(['success' => $isValid]);
    }

    public function actionValidateUrl(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $value = Craft::$app->getRequest()->getParam('value');

        /** @var Field $field */
        $field = $this->getFieldModel();
        $isValid = FormsModule::getInstance()->urlField->validate($value, $field);

        return $this->asJson(['success' => $isValid]);
    }

    public function actionValidateRegularExpression(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $value = Craft::$app->getRequest()->getParam('value');
        $field = $this->getFieldModel();

        if (!$field) {
            return $this->asJson(['success' => false]);
        }

        $isValid = FormsModule::getInstance()->regularExpressionField->validate($value, $field);

        return $this->asJson(['success' => $isValid]);
    }

    protected function getFieldModel(): Field
    {
        $oldFieldContext = Craft::$app->content->fieldContext;
        $fieldContext = Craft::$app->getRequest()->getParam('fieldContext');
        $fieldHandle = Craft::$app->getRequest()->getParam('fieldHandle');

        // Retrieve an Email Field, wherever it may be
        Craft::$app->content->fieldContext = $fieldContext;

        /** @var Field $field */
        $field = Craft::$app->fields->getFieldByHandle($fieldHandle);
        Craft::$app->content->fieldContext = $oldFieldContext;

        return $field;
    }
}
