<?php

use yii\helpers\Html;
use yii\widgets\Pjax;
use yii\grid\GridView;
use yii\bootstrap\Modal;
use app\helpers\GridViewHelper;
use app\services\DatabaseService;

/* @var $this yii\web\View */
/* @var $dbCompareService \app\services\DatabaseCompareService */


$this->params['breadcrumbs'][] = 'Database';
?>

<div class="database__list">
    <div class="database-left__list col-md-5">
        <div class="database-left__title">
            <h4>Database: <?php echo DatabaseService::getDbName(Yii::$app->left_db->dsn) ?></h4>
            <?php if($c = $dbCompareService->getLeftDbCountNewTables()): ?>
                <p>Новых таблиц: <?php echo $c; ?></p>
            <?php endif; ?>
            <?php if($c = $dbCompareService->getLeftDbCountEditedSchemaTables()): ?>
                <p>Таблиц с измененной схемой: <?php echo $c; ?></p>
            <?php endif; ?>
            <?php if($c = $dbCompareService->getLeftDbCountDroppedTables()): ?>
                <p>Удаленных таблиц: <?php echo $c; ?></p>
            <?php endif; ?>
        </div>
        <?php \yii\widgets\Pjax::begin([
            'id' => 'left-database-pjax-id'
        ]); ?>
        <?php echo GridView::widget([
            'id' => 'left-database',
            'dataProvider' => $dbCompareService->getLeftDbDataProvider(),
            'pager' => ['maxButtonCount' => 5],
            'rowOptions'=>function($data, $table_name, $index, $grid) {
                return GridViewHelper::dbTableRowRenderer($data, $table_name, $index, $grid);
            },
            'columns' => [
                [
                    'class' => 'yii\grid\CheckboxColumn',
                ],
                [
                    'class' => 'yii\grid\SerialColumn'
                ],
                [
                    'label' => "Название таблицы",
                    'options' => [
                        'database' => 'left_db'
                    ],
                    'contentOptions' => [
                        'class' => 'table-name'
                    ],
                    'attribute' => 'title',
                    'format' => 'raw',
                    'value' => function($data, $table_name, $index, $grid){
                        return GridViewHelper::dbTableColumnRenderer($data, $table_name, $index, $grid);
                    }
                ],
            ],
        ]); ?>
        <?php \yii\widgets\Pjax::end(); ?>
    </div>
    <div class="database-separator__list col-md-2">
        <div class="database-separator__diagram">
            <div class="database-separator__left_db" title="Источник"></div>
            <div class="database-separator__arrow"></div>
            <div class="database-separator__right_db" title="Получатель"></div>
        </div>
    </div>
    <div class="database-right__list col-md-5">
        <div class="database-right__title">
            <h4>Database: <?php echo DatabaseService::getDbName(Yii::$app->right_db->dsn) ?></h4>
            <?php if($c = $dbCompareService->getRightDbCountNewTables()): ?>
                <p>Новых таблиц: <?php echo $c; ?></p>
            <?php endif; ?>
            <?php if($c = $dbCompareService->getRightDbCountEditedSchemaTables()): ?>
                <p>Таблиц с измененной схемой: <?php echo $c; ?></p>
            <?php endif; ?>
            <?php if($c = $dbCompareService->getRightDbCountDroppedTables()): ?>
                <p>Удаленных таблиц: <?php echo $c; ?></p>
            <?php endif; ?>
        </div>
        <?php \yii\widgets\Pjax::begin([
            'id' => 'right-database-pjax-id'
        ]); ?>
        <?php echo GridView::widget([
            'id' => 'right-database',
            'dataProvider' => $dbCompareService->getRightDbDataProvider(),
            'pager' => ['maxButtonCount' => 5],
            'rowOptions'=>function($data, $table_name, $index, $grid) {
                return GridViewHelper::dbTableRowRenderer($data, $table_name, $index, $grid);
            },
            'columns' => [
                [
                    'class' => 'yii\grid\CheckboxColumn',
                ],
                [
                    'class' => 'yii\grid\SerialColumn'
                ],
                [
                    'label' => "Название таблицы",
                    'options' => [
                        'database' => 'right_db'
                    ],
                    'contentOptions' => [
                        'class' => 'table-name'
                    ],
                    'attribute' => 'title',
                    'format' => 'raw',
                    'value' => function($data, $table_name, $index, $grid){
                        return GridViewHelper::dbTableColumnRenderer($data, $table_name, $index, $grid);
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
<div class="database__notes">
    <div class="database__notes-new_table">
        <span class="square"></span>
        <div class="text">- новая таблица</div>
    </div>
    <div class="database__notes-edit-schema-table">
        <span class="square"></span>
        <div class="text">- изменения в схеме таблицы</div>
    </div>
    <div class="database__notes-edit-data-table">
        <span class="square"></span>
        <div class="text">- изменения в данных таблицы</div>
    </div>
    <div class="database__notes-edit-schema-and-data-table">
        <span class="square"></span>
        <div class="text">- изменения в схеме и данных таблицы</div>
    </div>
    <div class="database__notes-delete_table">
        <span class="square"></span>
        <div class="text">- таблица удалена</div>
    </div>
</div>
<div class="database__debug">
    <pre>
        <?php
//        echo "В правой бд нет таблиц:\n";
//        var_dump($leftDatabaseDiff);
//        echo "В левой бд нет таблиц:\n";
//        var_dump($rightDatabaseDiff);

        echo "Разница в схеме слева:\n";
        var_dump($dbCompareService->getRightDbTableDataDiff());
        echo "Разница в схеме справа:\n";
        var_dump($dbCompareService->getLeftDbTableDataDiff());

        ?>
    </pre>
</div>
<div class="database__modals">
    <?php
    Modal::begin([
        'options' => [
            'id' => 'database__modal'
        ],
        'header' => '<h2>Изменения данных в таблице</h2>',
        'toggleButton' => false,
        'footer' => '<button type="button" class="start-process btn btn-success">Start process</button>',
    ]);

    Modal::end();
    ?>
    <?php Modal::begin([
        'options' => [
            'id' => 'table-confirm__modal'
        ],
        'header' => '<h2>Применить изменения</h2>',
        'toggleButton' => false,
        'footer' => '<button class="start-process__row-data btn btn-success" type="submit">Apply</button>',
    ]); ?>

    <textarea class="table-changes__textbox"></textarea>

    <?php Modal::end(); ?>
</div>