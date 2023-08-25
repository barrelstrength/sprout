<?php

namespace BarrelStrength\Sprout\mailer\controllers;

use BarrelStrength\Sprout\mailer\components\elements\email\EmailElement;
use BarrelStrength\Sprout\mailer\emailthemes\EmailThemeHelper;
use BarrelStrength\Sprout\mailer\emailvariants\EmailVariant;
use BarrelStrength\Sprout\mailer\mailers\MailerHelper;
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

        $emailThemes = EmailThemeHelper::getEmailThemes();

        return $this->renderTemplate('sprout-module-mailer/email/index.twig', [
            'title' => $elementType::pluralDisplayName(),
            'elementType' => $elementType,
            'newButtonLabel' => $newButtonLabel,
            'newEmailUrl' => $newEmailUrl,
            'selectedSubnavItem' => $emailVariant::refHandle(),
            'emailVariantHandle' => $emailVariant::refHandle(),
            'emailThemes' => $emailThemes,
        ]);
    }

    public function actionCreateEmail(string $emailVariant = null): Response
    {
        $site = Cp::requestedSite();

        if (!$site instanceof Site) {
            throw new ForbiddenHttpException('User not authorized to edit content in any sites.');
        }

        $email = Craft::createObject(EmailElement::class);
        $email->emailThemeUid = Craft::$app->getRequest()->getRequiredParam('emailThemeUid');

        if (!$email->emailThemeUid) {
            throw new NotFoundHttpException('No email themes exist.');
        }

        $emailVariant = new $emailVariant();

        if (!$emailVariant instanceof EmailVariant) {
            throw new NotFoundHttpException('No email variant found.');
        }

        $defaultMailer = MailerHelper::getDefaultMailer();

        $email->emailVariantType = $emailVariant::class;
        $email->mailerUid = $defaultMailer->uid ?? null;

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
