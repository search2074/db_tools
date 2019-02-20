<?php

use yii\helpers\Html;
use yii\widgets\Pjax;
use yii\grid\GridView;
use yii\bootstrap\Modal;
use app\helpers\GridViewHelper;

/* @var $this yii\web\View */
/* @var $tableCompareService \app\services\TableCompareService */


$this->params['breadcrumbs'][] = 'Compare table data';
?>

<div class="table__list">
    <div class="table-left__list col-md-6">
        <?php \yii\widgets\Pjax::begin([
            'id' => 'left-table-pjax-id'
        ]); ?>
        <?php echo GridView::widget([
            'id' => 'left-table',
            'dataProvider' => $tableCompareService->getLeftDbDataProvider(),
            'pager' => ['maxButtonCount' => 5],
            'rowOptions'=>function($data, $pk, $index, $grid) {
                return GridViewHelper::tableRecordRowRenderer($data, $pk, $index, $grid);
            },
            'columns' => GridViewHelper::getTableColumns($tableCompareService->getComparedData('columns'), 'left_table')
        ]); ?>
        <?php \yii\widgets\Pjax::end(); ?>
    </div>
    <div class="table-right__list col-md-6">
        <?php \yii\widgets\Pjax::begin([
            'id' => 'right-table-pjax-id'
        ]); ?>
        <?php echo GridView::widget([
            'id' => 'right-table',
            'dataProvider' => $tableCompareService->getRightDbDataProvider(),
            'pager' => ['maxButtonCount' => 5],
            'rowOptions'=>function($data, $pk, $index, $grid) {
                return GridViewHelper::tableRecordRowRenderer($data, $pk, $index, $grid);
            },
            'columns' => GridViewHelper::getTableColumns($tableCompareService->getComparedData('columns'), 'right_table')
        ]); ?>
        <?php \yii\widgets\Pjax::end(); ?>
    </div>
</div>
