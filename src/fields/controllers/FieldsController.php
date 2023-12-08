<?php

namespace BarrelStrength\Sprout\fields\controllers;

use BarrelStrength\Sprout\fields\helpers\PhoneHelper;
use Craft;
use craft\web\Controller as BaseController;
use yii\web\Response;

class FieldsController extends BaseController
{
    public function actionValidatePhone(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $value = Craft::$app->getRequest()->getParam('value');

        $phone = $value['phone'] ?? null;
        $country = $value['country'] ?? null;

        $isValid = PhoneHelper::validatePhone($phone, $country);

        return $this->asJson(['success' => $isValid]);
    }
}
