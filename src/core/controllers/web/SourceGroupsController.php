<?php

namespace BarrelStrength\Sprout\core\controllers\web;

use BarrelStrength\Sprout\core\sourcegroups\SourceGroupRecord;
use Craft;
use craft\web\Controller;
use yii\web\Response;

class SourceGroupsController extends Controller
{
    public function actionSaveSourceGroup(): Response
    {
        $this->requirePostRequest();
        $this->requireAdmin(false);

        $request = Craft::$app->getRequest();
        $groupId = $request->getBodyParam('id');

        if ($groupId) {
            $group = SourceGroupRecord::findOne($groupId);
        } else {
            $group = new SourceGroupRecord();
        }

        $isNew = !$groupId;
        $group->name = $request->getRequiredBodyParam('name');
        $group->type = $request->getRequiredBodyParam('type');

        if (!$group->save(true)) {
            return $this->asJson([
                'errors' => $group->getErrors(),
            ]);
        }

        if ($isNew) {
            Craft::$app->getSession()->setNotice(Craft::t('sprout-module-core', 'Group added.'));
        }

        return $this->asJson([
            'success' => true,
            'group' => $group->getAttributes(),
        ]);
    }

    public function actionDeleteSourceGroup(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();
        $this->requireAdmin(false);

        $request = Craft::$app->getRequest();

        $groupId = $request->getRequiredBodyParam('id');

        $group = SourceGroupRecord::findOne($groupId);

        if (!$group || !$group->delete()) {

            $message = Craft::t('sprout-module-core', 'Unable to delete group.');
            Craft::$app->getSession()->setNotice($message);

            return $this->asJson([
                'success' => false,
            ]);
        }

        $message = Craft::t('sprout-module-core', 'Group deleted.');
        Craft::$app->getSession()->setNotice($message);

        return $this->asJson([
            'success' => true,
        ]);
    }
}
