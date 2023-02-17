<?php

namespace BarrelStrength\Sprout\mailer\controllers;

use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\mailer\email\EmailType;
use BarrelStrength\Sprout\mailer\MailerModule;
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
    public function actionEmailIndexTemplate(string $emailType = null): Response
    {
        /** @var string|EmailType $emailType */
        if (!$emailType = MailerModule::getInstance()->emailTypes->getEmailTypeByHandle($emailType)) {
            throw new InvalidArgumentException('Unable to find email type: ' . $emailType);
        }

        //        $emailType->getPermission()
        //        $this->requirePermission('sprout-module-mailer:editCampaigns');

        /** @var string|Element $elementType */
        $elementType = $emailType::getElementIndexType();

        $newButtonUrl = UrlHelper::cpUrl('sprout/email/' . $elementType::refHandle() . '/new');
        $newButtonLabel = Craft::t('sprout-module-mailer', 'New Email');

        return $this->renderTemplate('sprout-module-mailer/email/index', [
            'elementType' => $elementType,
            'title' => $elementType::pluralDisplayName(),
            'newButtonLabel' => $newButtonLabel,
            'newButtonUrl' => $newButtonUrl,
        ]);
    }

    public function actionCreateEmail(string $emailType = null): Response
    {
        $site = Cp::requestedSite();

        if (!$site instanceof Site) {
            throw new ForbiddenHttpException('User not authorized to edit content in any sites.');
        }

        $email = Craft::createObject(EmailElement::class);
        //        $email->siteId = $site->id;

        $email->emailThemeId = MailerModule::getInstance()->emailThemes->getDefaultEmailTheme();

        if (!$email->emailThemeId) {
            throw new NotFoundHttpException('No email themes exist.');
        }

        $emailType = MailerModule::getInstance()->emailTypes->getEmailTypeByHandle($emailType);

        if (!$emailType instanceof EmailType) {
            throw new NotFoundHttpException('No email type found.');
        }

        $email->emailType = $emailType::class;
        $email->mailerId = MailerModule::getInstance()->mailers->getDefaultMailerId();

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

        return $this->renderTemplate('sprout-module-mailer/email/prepare', [
            'element' => $element,
        ]);
    }
}
