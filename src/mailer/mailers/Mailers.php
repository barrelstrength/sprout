<?php

namespace BarrelStrength\Sprout\mailer\mailers;

use Craft;
use craft\base\Component;
use craft\events\ConfigEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\helpers\Json;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use Throwable;
use yii\base\Exception;

class Mailers extends Component
{
    public const EVENT_REGISTER_MAILER_TYPES = 'registerSproutMailerTypes';

    protected array $mailers = [];

    /**
     * @return Mailer[]
     */
    public function getRegisteredMailers(): array
    {
        $mailers = [];
        $mailers[] = SystemMailer::class;

        $event = new RegisterComponentTypesEvent([
            'types' => $mailers,
        ]);

        $this->trigger(self::EVENT_REGISTER_MAILER_TYPES, $event);

        $eventMailers = $event->types;

        $mailers = [];

        foreach ($eventMailers as $eventMailerClassName) {
            $mailers[$eventMailerClassName] = new $eventMailerClassName();
        }

        return $mailers;
    }

    public function getMailerByName($name = null): Mailer
    {
        $this->mailers = $this->getRegisteredMailers();

        $mailer = $this->mailers[$name] ?? null;

        if (!$mailer instanceof Mailer) {
            throw new Exception('Mailer not found: ' . $name);
        }

        return $mailer;
    }

    public function getMailers(): array
    {
        $mailers = [];
        $mailerRecords = MailerRecord::find()
            ->all();

        foreach ($mailerRecords as $mailerRecord) {
            $mailer = new $mailerRecord->type();

            $mailer->id = $mailerRecord->id;
            $mailer->name = $mailerRecord->name;

            $settings = Json::decode($mailerRecord->settings);
            $mailer->setAttributes($settings, false);
            $mailer->uid = $mailerRecord->uid;

            $mailers[] = $mailer;
        }

        return $mailers;
    }

    public function getMailerById(int $mailerId): ?Mailer
    {
        $mailerRecord = MailerRecord::findOne($mailerId);

        $mailer = new $mailerRecord->type();

        $mailer->id = $mailerRecord->id;
        $mailer->name = $mailerRecord->name;

        $settings = Json::decode($mailerRecord->settings);
        $mailer->setAttributes($settings, false);
        $mailer->uid = $mailerRecord->uid;

        return $mailer;
    }

    public function getDefaultMailerId(): int
    {
        return MailerRecord::find()
            ->select('id')
            ->scalar();
    }

    public function handleChangedMailer(ConfigEvent $event): void
    {
        $mailerUid = $event->tokenMatches[0];
        $data = $event->newValue;

        ProjectConfigHelper::ensureAllSitesProcessed();

        $mailerRecord = MailerRecord::find()
            ->where(['uid' => $mailerUid])
            ->one();

        if (!$mailerRecord) {
            $mailerRecord = new MailerRecord();
        }

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {

            $mailerRecord->name = $data['name'];
            $mailerRecord->type = $data['type'];
            $mailerRecord->settings = $data['settings'];
            $mailerRecord->uid = $mailerUid;

            $mailerRecord->save();

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    public function handleDeletedMailer(ConfigEvent $event): void
    {
        //        \Craft::dd($event);
        //        Craft::$app->getFields()->deleteLayoutsByType(RedirectElement::class);
    }
}
