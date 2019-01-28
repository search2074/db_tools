<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
/* @var $this yii\web\View */
use app\services\DatabaseService;
/* @var $leftDbDataProvider \yii\data\ArrayDataProvider */
/* @var $rightDbDataProvider \yii\data\ArrayDataProvider */


$this->params['breadcrumbs'][] = $this->title;

?>

<div class="database__list">
    <div class="database-left__list col-md-5">
        <div class="database-left__title">
            <h4>Database: <?php echo DatabaseService::getDbName(Yii::$app->left_db->dsn) ?></h4>
        </div>
        <?php echo GridView::widget([
            'dataProvider' => $leftDbDataProvider,
            'pager' => ['maxButtonCount' => 5],
            'columns' => [
                ['class' => 'yii\grid\SerialColumn'],
                [
                    'label' =>"Название таблицы",
                    'attribute' => 'title',
                    'value'=>function($value, $key){
                        return $value;
                    }
                ],
                ['class' => 'yii\grid\ActionColumn'],
            ],
        ]); ?>
    </div>
    <div class="database-separator__list col-md-2">
        <div class="database-separator__diagram">
            <div class="database-separator__left_db"></div>
            <div class="database-separator__arrow"></div>
            <div class="database-separator__right_db"></div>
        </div>
    </div>
    <div class="database-right__list col-md-5">
        <div class="database-right__title">
            <h4>Database: <?php echo DatabaseService::getDbName(Yii::$app->right_db->dsn) ?></h4>
        </div>
        <?php echo GridView::widget([
            'dataProvider' => $rightDbDataProvider,
            'pager' => ['maxButtonCount' => 5],
            'columns' => [
                ['class' => 'yii\grid\SerialColumn'],
                [
                    'label' =>"Название таблицы",
                    'attribute' => 'title',
                    'value'=>function($value, $key){
                        return $value;
                    }
                ],
                ['class' => 'yii\grid\ActionColumn'],
            ],
        ]); ?>
    </div>
</div>
