<?php

namespace BarrelStrength\Sprout\datastudio\controllers;

use BarrelStrength\Sprout\datastudio\components\elements\DataSetElement;
use BarrelStrength\Sprout\datastudio\datasets\DataSetHelper;
use BarrelStrength\Sprout\datastudio\DataStudioModule;
use BarrelStrength\Sprout\datastudio\reports\ExportHelper;
use Craft;
use craft\base\Element;
use craft\errors\ElementNotFoundException;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\helpers\Template;
use craft\helpers\UrlHelper;
use craft\models\Site;
use craft\web\Controller;
use Twig\Markup;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

class DataSetController extends Controller
{
    public function actionDataSetIndexTemplate($groupId = null): Response
    {
        $site = Cp::requestedSite();

        if (!$site instanceof Site) {
            throw new ForbiddenHttpException('User not authorized to edit content in any sites.');
        }

        $this->requirePermission(DataStudioModule::p('accessModule'));

        return $this->renderTemplate('sprout-module-data-studio/_datasets/index', [
            'title' => DataSetElement::pluralDisplayName(),
            'elementType' => DataSetElement::class,
            'groupId' => $groupId,
            'newDataSetButtonHtml' => self::getNewDataSetButtonHtml($site),
        ]);
    }

    public function actionResultsIndexTemplate(DataSetElement $dataSet = null, int $dataSetId = null): Response
    {
        $site = Cp::requestedSite();

        if (!$site instanceof Site) {
            throw new ForbiddenHttpException('User not authorized to edit content in any sites.');
        }

        if ($dataSet === null && $dataSetId) {
            $dataSet = Craft::$app->elements->getElementById($dataSetId, DataSetElement::class, $site->id);
        }

        if (!$dataSet) {
            throw new NotFoundHttpException('Data set not found.');
        }

        $currentUser = Craft::$app->getUser()->getIdentity();

        $dataSource = $dataSet->getDataSource();

        if (!$currentUser->can(DataStudioModule::p('viewReports:' . $dataSource::class))) {
            throw new ForbiddenHttpException('User is not authorized to perform this action.');
        }

        if (!$dataSource) {
            throw new NotFoundHttpException('Data Source not found.');
        }

        [$labels, $values] = DataSetHelper::getLabelsAndValues($dataSet, $dataSource);

        //$visualizationSettings = $dataSet->getSetting('visualization');
        //
        //$visualizationType = $visualizationSettings['type'] ?? null;
        //$visualization = class_exists($visualizationType) ? new $visualizationType() : null;
        //
        //if ($visualization instanceof Visualization) {
        //    $visualization->setSettings($visualizationSettings);
        //    $visualization->setLabels($labels);
        //    $visualization->setValues($values);
        //} else {
        //    $visualization = null;
        //}

        if ($visualization = $dataSet->getVisualization()) {
            // @todo - review the setLabels/setValues stuff.
            // May duplicate efforts on dataSet and visualization...
            $visualization->setLabels($labels);
            $visualization->setValues($values);
        }

        $currentUser = Craft::$app->getUser()->getIdentity();

        $label = Craft::t('sprout-module-data-studio', 'Export');

        $disabledExportButtonHtml = Html::submitButton($label, [
            'class' => ['btn', 'disabled'],
            'id' => 'btn-download-csv',
            'href' => DataStudioModule::getUpgradeUrl(),
            'title' => DataStudioModule::getUpgradeMessage(),
            'style' => 'cursor: not-allowed;',
            'disabled' => 'disabled',
        ]);

        return $this->renderTemplate('sprout-module-data-studio/_datasets/results', [
            'dataSet' => $dataSet,
            'visualization' => $visualization,
            'dataSource' => $dataSource,
            'labels' => $labels,
            'values' => $values,
            'canEditDataSet' => $currentUser->can(DataStudioModule::p('editDataSet:' . $dataSource::class)),
            'disabledExportButtonHtml' => Template::raw($disabledExportButtonHtml),
        ]);
    }

    public function actionCreateDataSet(string $type): Response
    {
        $site = Cp::requestedSite();

        if (!$site instanceof Site) {
            throw new ForbiddenHttpException('User not authorized to edit content in any sites.');
        }

        $dataSet = Craft::createObject(DataSetElement::class);
        $dataSet->siteId = $site->id;

        $dataSourceType = DataStudioModule::getInstance()->dataSources->getDataSourceTypeByHandle($type);

        if (!$dataSourceType) {
            throw new NotFoundHttpException('Data Source handle not found.');
        }

        $dataSet->type = $dataSourceType;

        $user = Craft::$app->getUser()->getIdentity();

        if (!$dataSet->canSave($user)) {
            throw new ForbiddenHttpException('User not authorized to save this data set.');
        }

        $dataSet->setScenario(Element::SCENARIO_ESSENTIALS);

        if (!Craft::$app->getDrafts()->saveElementAsDraft($dataSet, Craft::$app->getUser()->getId(), null, null, false)) {
            throw new ServerErrorHttpException(sprintf('Unable to save data set as a draft: %s', implode(', ', $dataSet->getErrorSummary(true))));
        }

        return $this->redirect($dataSet->getCpEditUrl());
    }

    public function actionExportDataSet(): void
    {
        if (!DataStudioModule::isPro()) {
            throw new ForbiddenHttpException('Upgrade to Sprout Data Studio Pro to export data sets.');
        }

        $currentUser = Craft::$app->getUser()->getIdentity();
        $dataSetId = Craft::$app->getRequest()->getParam('dataSetId');

        /** @var DataSetElement $dataSet */
        $dataSet = Craft::$app->elements->getElementById($dataSetId, DataSetElement::class);

        if (!$dataSet) {
            throw new ElementNotFoundException('Data set not found');
        }

        $dataSource = $dataSet->getDataSource();

        if (!$currentUser->can(DataStudioModule::p('viewReports:' . $dataSource::class))) {
            throw new ForbiddenHttpException('User not authorized to view this data set.');
        }

        $filename = $dataSet . '-' . date('Ymd-his');

        $dataSource->isExport = true;
        $labels = $dataSource->getDefaultLabels($dataSet);
        $values = $dataSource->getResults($dataSet);

        ExportHelper::toCsv($values, $labels, $filename, $dataSet->delimiter);
    }

    public function actionUpdateDataSet(): ?Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $dataSetId = $request->getBodyParam('dataSetId');
        $settings = $request->getBodyParam('settings');

        $dataSet = new DataSetElement();

        if ($dataSetId && $settings) {
            /** @var DataSetElement $dataSet */
            $dataSet = Craft::$app->getElements()->getElementById($dataSetId, DataSetElement::class);

            if (!$dataSet) {
                throw new NotFoundHttpException('No data set exists with the ID: ' . $dataSetId);
            }

            $currentUser = Craft::$app->getUser()->getIdentity();
            $dataSource = $dataSet->getDataSource();

            if (!$currentUser->can(DataStudioModule::p('editDataSet:' . $dataSource::class))) {
                throw new NotFoundHttpException('User does not have permission to access Data Set: ' . $dataSetId);
            }

            $dataSet->settings = $settings;
        }

        if (!Craft::$app->getElements()->saveElement($dataSet)) {

            Craft::$app->getSession()->setError(Craft::t('sprout-base-reports', 'Could not update report.'));

            // Send the report back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'dataSet' => $dataSet,
            ]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('sprout-module-data-studio', 'Data set updated.'));

        return $this->redirectToPostedUrl($dataSet);
    }

    /**
     * Because Garnish isn't documented, still.
     */
    public function actionGetNewDataSetsButtonHtml(): Response
    {
        $this->requireAcceptsJson();

        $site = Cp::requestedSite();

        if (!$site instanceof Site) {
            throw new ForbiddenHttpException('User not authorized to edit content in any sites.');
        }

        return $this->asJson([
            'html' => self::getNewDataSetButtonHtml($site),
        ]);
    }

    public static function getNewDataSetButtonHtml(Site $site): ?Markup
    {
        $dataSources = DataStudioModule::getInstance()->dataSources->getDataSources();

        $newDataSetOptions = [];

        foreach ($dataSources as $dataSource) {
            $currentUser = Craft::$app->getUser()->getIdentity();

            if (!$currentUser->can(DataStudioModule::p('editDataSet:' . $dataSource::class))) {
                continue;
            }

            $newDataSetOptions[] = [
                'name' => $dataSource::displayName(),
                'url' => UrlHelper::cpUrl('sprout/data-studio/new/' . $dataSource::getHandle(), [
                    'site' => $site->handle,
                ]),
            ];
        }

        $label = Craft::t('sprout-module-data-studio', 'New {displayName}', [
            'displayName' => DataSetElement::displayName(),
        ]);

        $labelHtml = Html::button($label, [
            'class' => 'btn menubtn submit add icon',
        ]);

        $menuListHtml = Html::ul($newDataSetOptions, [
            'item' => function($item) {
                return Html::tag('li', Html::a($item['name'], $item['url'], [
                    'class' => 'formsubmit sprout-dataset-new-button',
                ]));
            },
        ]);

        $menuHtml = Html::tag('div', $menuListHtml, [
            'class' => 'menu',
        ]);

        $buttonHtml = Html::tag('div', $labelHtml . $menuHtml, [
            'id' => 'sprout-new-dataset-btn',
            'class' => 'btngroup',
        ]);

        return Template::raw($buttonHtml);
    }
}
