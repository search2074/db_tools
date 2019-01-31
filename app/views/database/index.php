<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
use app\services\DatabaseService;

/* @var $this yii\web\View */
/* @var $leftDbDataProvider \yii\data\ArrayDataProvider */
/* @var $rightDbDataProvider \yii\data\ArrayDataProvider */
/* @var $leftDatabaseDiff array */
/* @var $rightDatabaseDiff array */

$this->params['breadcrumbs'][] = 'Database';
?>

<div class="database__list">
    <div class="database-left__list col-md-5">
        <div class="database-left__title">
            <h4>Database: <?php echo DatabaseService::getDbName(Yii::$app->left_db->dsn) ?></h4>
            <p>Новых таблиц: <?php echo count($leftDatabaseDiff['new_tables']); ?></p>
        </div>
        <?php \yii\widgets\Pjax::begin([
            'enablePushState'=>FALSE
        ]); ?>
        <?php echo GridView::widget([
            'id' => 'left-database',
            'dataProvider' => $leftDbDataProvider,
            'pager' => ['maxButtonCount' => 5],
            'rowOptions'=>function($table_name) use ($leftDatabaseDiff) {
                if(in_array($table_name, $leftDatabaseDiff['new_tables'])){
                    return [
                        'title' => 'Новая таблица',
                        'class' => 'success'
                    ];
                }
            },
            'columns' => [
                ['class' => 'yii\grid\CheckboxColumn',],
                    ['class' => 'yii\grid\SerialColumn'],
                [
                    'label' =>"Название таблицы",
                    'contentOptions' => ['class' => 'table-name'],
                    'attribute' => 'title',
                    'value'=>function($value, $key){
                        return $value;
                    }
                ],
            ],
        ]); ?>
        <?php \yii\widgets\Pjax::end(); ?>
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
            <p>Новых таблиц: <?php echo count($rightDatabaseDiff['new_tables']); ?></p>
        </div>
        <?php \yii\widgets\Pjax::begin([
            'enablePushState'=>FALSE
        ]); ?>
        <?php echo GridView::widget([
            'id' => 'right-database',
            'dataProvider' => $rightDbDataProvider,
            'pager' => ['maxButtonCount' => 5],
            'rowOptions'=>function($table_name) use ($rightDatabaseDiff) {
                if(in_array($table_name, $rightDatabaseDiff['new_tables'])){
                    return [
                        'title' => 'Новая таблица',
                        'class' => 'success'
                    ];
                }
            },
            'columns' => [
                ['class' => 'yii\grid\CheckboxColumn',],
                ['class' => 'yii\grid\SerialColumn'],
                [
                    'label' =>"Название таблицы",
                    'contentOptions' => ['class' => 'table-name'],
                    'attribute' => 'title',
                    'value'=>function($value, $key){
                        return $value;
                    }
                ],
            ],
        ]); ?>
        <?php \yii\widgets\Pjax::end(); ?>
    </div>
</div>
<div class="database__contols">
    <div class="col-md-5"></div>
    <div class="col-md-2">
        <button type="button" class="start-process btn btn-success">Start process</button>
    </div>
    <div class="col-md-5"></div>
</div>
<div class="database__debug">
    <pre>
        <?php
        echo "В правой бд нет таблиц:\n";
        var_dump($leftDatabaseDiff);
        echo "В левой бд нет таблиц:\n";
        var_dump($rightDatabaseDiff);

        ?>
    </pre>
</div>
