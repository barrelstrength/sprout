<?php

namespace BarrelStrength\Sprout\mailer\controllers;

use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\mailer\emailtypes\EmailTypeHelper;
use BarrelStrength\Sprout\mailer\emailvariants\EmailVariant;
use Craft;
use craft\base\Element;
use craft\errors\ElementNotFoundException;
use craft\helpers\Cp;
use craft\helpers\UrlHelper;
use craft\models\Site;
use craft\web\Controller;
use http\Exception\InvalidArgumentException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

class EmailController extends Controller
{
    public function actionEmailIndexTemplate(string $emailVariant = null): Response
    {
        /** @var string|EmailVariant $emailVariant */
        if (!$emailVariant = new $emailVariant()) {
            throw new InvalidArgumentException('Unable to find email variant: ' . $emailVariant);
        }

        /** @var string|Element $elementType */
        $elementType = $emailVariant::elementType();

        $newEmailUrl = UrlHelper::cpUrl('sprout/email/' . $emailVariant::refHandle() . '/new');
        $newButtonLabel = Craft::t('sprout-module-mailer', 'New Email');

        $emailTypes = EmailTypeHelper::getEmailTypes();

        return $this->renderTemplate('sprout-module-mailer/email/index.twig', [
            'title' => $elementType::pluralDisplayName(),
            'elementType' => $elementType,
            'newButtonLabel' => $newButtonLabel,
            'newEmailUrl' => $newEmailUrl,
            'selectedSubnavItem' => $emailVariant::refHandle(),
            'emailVariantHandle' => $emailVariant::refHandle(),
            'emailTypes' => $emailTypes,
        ]);
    }

    public function actionCreateEmail(string $emailVariant = null): Response
    {
        $site = Cp::requestedSite();

        if (!$site instanceof Site) {
            throw new ForbiddenHttpException('User not authorized to edit content in any sites.');
        }

        $email = Craft::createObject(EmailElement::class);
        $email->emailTypeUid = Craft::$app->getRequest()->getRequiredParam('emailTypeUid');

        if (!$email->emailTypeUid) {
            throw new NotFoundHttpException('No email types exist.');
        }

        $emailVariant = new $emailVariant();

        if (!$emailVariant instanceof EmailVariant) {
            throw new NotFoundHttpException('No email variant found.');
        }

        $email->emailVariantType = $emailVariant::class;

        if ($emailVariantSettings = Craft::$app->request->getParam('emailVariantSettings')) {
            $email->emailVariantSettings = $emailVariantSettings;
        }

        $user = Craft::$app->getUser()->getIdentity();

        if (!$email->canSave($user)) {
            throw new ForbiddenHttpException('User not authorized to save this email.');
        }

        $email->setScenario(Element::SCENARIO_ESSENTIALS);

        if (!Craft::$app->getDrafts()->saveElementAsDraft($email, Craft::$app->getUser()->getId(), null, null, false)) {
            throw new ServerErrorHttpException(sprintf('Unable to save email as a draft: %s', implode(', ', $email->getErrorSummary(true))));
        }

        return $this->redirect($email->getCpEditUrl());
    }

    public function actionPrepareSendTemplate(): Response
    {
        $elementId = Craft::$app->getRequest()->getRequiredQueryParam('elementId');
        $element = Craft::$app->getElements()->getElementById($elementId, EmailElement::class);

        if (!$element) {
            throw new ElementNotFoundException();
        }

        return $this->renderTemplate('sprout-module-mailer/email/prepare.twig', [
            'element' => $element,
        ]);
    }
}
