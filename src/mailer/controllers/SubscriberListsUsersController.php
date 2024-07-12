<?php

namespace BarrelStrength\Sprout\mailer\controllers;

use BarrelStrength\Sprout\mailer\components\audiences\SubscriberListAudienceType;
use BarrelStrength\Sprout\mailer\components\elements\audience\AudienceElement;
use BarrelStrength\Sprout\mailer\components\elements\subscriber\SproutSubscriberElementBehavior;
use BarrelStrength\Sprout\mailer\MailerModule;
use BarrelStrength\Sprout\mailer\subscriberlists\SubscriptionRecord;
use Craft;
use craft\controllers\EditUserTrait;
use craft\elements\User;
use craft\helpers\Html;
use craft\web\Controller;
use craft\web\CpScreenResponseBehavior;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class SubscriberListsUsersController extends Controller
{
    use EditUserTrait;

    public const SCREEN_SUBSCRIBER_LISTS = 'sprout/subscriber-lists';

    public function actionIndex(?int $userId = null): Response
    {
        /** @var User|SproutSubscriberElementBehavior $user */
        $user = $this->editedUser($userId);

        /** @var Response|CpScreenResponseBehavior $response */
        $response = $this->asEditUserScreen($user, 'sprout/subscriber-lists');

        $content = Craft::$app->getView()->renderTemplate('sprout-module-mailer/subscribers/_fields.twig', [
            'options' => self::getSubscriberListOptions(),
            'values' => array_keys($user->getSproutSubscriptions()),
        ]);

        return $response->contentHtml($content);
    }

    protected static function getSubscriberListOptions(): array
    {
        /** @var AudienceElement[] $audiences */
        $audiences = AudienceElement::find()
            ->type(SubscriberListAudienceType::class)
            ->all();

        $options = [];

        array_map(static function($audience) use (&$options) {
            $options[] = [
                'label' => $audience->name,
                'value' => $audience->getId(),
            ];
        }, $audiences);

        return $options;
    }
}
