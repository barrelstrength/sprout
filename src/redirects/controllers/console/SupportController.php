<?php

namespace BarrelStrength\Sprout\redirects\controllers\console;

use BarrelStrength\Sprout\redirects\components\elements\RedirectElement;
use Craft;
use craft\console\Controller;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Console;
use craft\records\Structure;
use yii\console\ExitCode;

class SupportController extends Controller
{
    /**
     * SUPPORT: Reset Redirects Structure UID. All existing Redirect Elements will be lost.
     * - Utility method to help with support where the Redirects Structure UID setting is incorrect
     * - Creates a new Structure and updates the Redirect module `structureUid` setting
     * - Does not migrate existing Redirects, they will be lost in the DB if they exist
     * - Best to use if no Redirects exist in DB
     *
     * @console craft sprout-module-redirects/support/hard-reset-structure-uid
     */
    public function actionHardResetStructureUid(): int
    {
        $this->stdout('Creating New Redirect Structure' . PHP_EOL, Console::FG_GREY);

        $structure = new Structure();
        $structure->maxLevels = 1;

        if (!$structure->save()) {
            $this->stdout('Unable to create Structure Element for Redirects.' . PHP_EOL, Console::FG_RED);

            return ExitCode::UNSPECIFIED_ERROR;
        }

        Craft::$app->getProjectConfig()->set('sprout.sprout-module-redirects.structureUid', $structure->uid, 'Update Redirect Structure UID');

        $this->stdout('Updated Redirect module `structureUid` setting in project config.' . PHP_EOL, Console::FG_GREEN);

        return ExitCode::OK;
    }

    /**
     * SUPPORT: Update Redirects Structure UID to match the Structure ID of the first Redirect Element found in the DB
     * - Utility method to help with support where the Redirects Structure UID setting is incorrect
     * - Retrieves the Redirect Structure UID from the first Redirect Element found in the DB
     * - Fails if no Redirect Elements exist in the DB
     *
     * @console craft sprout-module-redirects/support/hard-reset-structure-uid
     */
    public function actionRefreshStructureUid(): int
    {
        $this->stdout('Creating New Redirect Structure' . PHP_EOL, Console::FG_GREY);

        $redirect = RedirectElement::find()
            ->status(null)
            ->one();

        if (!$redirect) {
            $this->stdout('Unable to find an existing Redirect.' . PHP_EOL, Console::FG_RED);

            return ExitCode::UNSPECIFIED_ERROR;
        }

        $structureUid = (new Query())
            ->select('uid')
            ->from(Table::STRUCTURES)
            ->where(['id' => $redirect->structureId])
            ->scalar();

        if (!$structureUid) {
            $this->stdout('Unable to find an existing Structure.' . PHP_EOL, Console::FG_RED);

            return ExitCode::UNSPECIFIED_ERROR;
        }

        Craft::$app->getProjectConfig()->set('sprout.sprout-module-redirects.structureUid', $structureUid, 'Refresh Redirect Structure UID');

        $this->stdout('Updated Redirect module `structureUid` setting in project config.' . PHP_EOL, Console::FG_GREEN);

        return ExitCode::OK;
    }
}
