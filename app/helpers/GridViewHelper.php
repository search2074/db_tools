<?php

namespace app\helpers;

use Yii;
use yii\helpers\Html;
use yii\grid\GridView;
use yii\grid\DataColumn;

class GridViewHelper {
    public static function dbTableColumnRenderer($data, $table_name, $index, $grid){
        $result = Html::tag('div', $table_name, ['class' => 'table-name__title']);

        if($data['edited_schema_table']){
            $elements = "";

            foreach ($data['columns_diff'] as $column_name => $opts) {
                if(!empty($opts['added_column'])){
                    $elements .= Html::tag('li', "добавлено поле: {$column_name}");
                }

                if(!empty($opts['dropped_column'])){
                    $elements .= Html::tag('li', "удалено поле: {$column_name}");
                }

                if(!empty($opts['edited_column']) && !empty($opts['edited_columns'])){
                    $column_changes = Html::tag('li', "изменения в поле {$column_name}:", ['class' => 'column-changes__title']);

                    foreach ($opts['edited_columns'] as $key => $value) {
                        $column_changes .= Html::tag('li', "{$key}: {$value}", ['class' => 'column-changes__value']);
                    }

                    $elements .= Html::tag('ul', $column_changes, ['class' => 'column-changes__list']);
                }
            }

            $edited_columns = Html::tag('ul', $elements, ['class' => 'edited-columns__list']);
            $result .= Html::tag('div', $edited_columns, ['class' => 'edited-columns']);
        }

        if($data['edited_table_data']){
            $button = Html::tag('button', "показать изменения", [
                'class' => 'view-table-data-diff-btn btn btn-primary',
                'data-source_database' => $grid->options['database'],
                'data-table_name' => $table_name,
            ]);
            $result .= Html::tag('div', $button, ['class' => 'view-table-data-diff']);
        }

        return $result;
    }

    private static function getGroups($table_name){
        $groups = Yii::$app->params['tables_settings']['groups'];

        if(!empty($groups[$table_name])){
            return $groups[$table_name];
        }

        return [];
    }

    public static function dbTableRowRenderer($data, $table_name, $index, GridView $grid){
        $result = [];

        if($data['created_table']){
            $result = [
                'title' => 'новая таблица',
                'class' => 'success'
            ];
        }
        elseif($data['edited_schema_table'] && $data['edited_table_data']){
            $result = [
                'title' => 'изменения в схеме и данных относительно ' .
                    ($grid->options['id'] === 'left-database' ? 'получателя' : 'источника'),
                'class' => 'schema-and-data-color'
            ];
        }
        elseif($data['edited_schema_table']) {
            $result = [
                'title' => 'изменения в схеме относительно ' .
                    ($grid->options['id'] === 'left-database' ? 'получателя' : 'источника'),
                'class' => 'schema-color'
            ];
        }
        elseif($data['edited_table_data']) {
            $result = [
                'title' => 'изменения в данных относительно ' .
                    ($grid->options['id'] === 'left-database' ? 'получателя' : 'источника'),
                'class' => 'data-color'
            ];
        }
        elseif($data['dropped_table']) {
            $result = [
                'title' => 'таблица удалена в источнике',
                'class' => 'danger'
            ];
        }

        if(!empty($result)){
            $groups = self::getGroups($table_name);

            if(!empty($result)){
                $result['data-linked-tables'] = implode(',', $groups);
            }

            return $result;
        }
    }

    public static function getTableColumns($models, $db){
        if(empty($models)){
            return [];
        }

        $columns = [
            [
                'class' => 'yii\grid\CheckboxColumn'
            ],
        ];

        foreach ($models as $model) {
            $columns[] = [
                'label' => $model,
                'attribute' => $model,
                'options' => [
                    'database' => $db
                ],
                'format' => 'text',
//                'value' => function ($row, $value, $index, $column) {
//                    return Html::tag('div', $row[$column->attribute], [
//                        'class' => 'db-table__cell'
//                    ]);
//                },
                'headerOptions' => [],
                'contentOptions' => function ($row, $value, $index, $column) {
                    $result = [
                        'class' => 'row-name',
                    ];

                    if(!empty($row['__record_modify_values']) && isset($row['__record_modify_values'][$column->attribute])){
                        $result['class'] .= ' column-modified';
                        $result['title'] = 'Измененное значение: '.$row['__record_modify_values'][$column->attribute];
                    }

                    return $result;
                }
            ];
        }

        return $columns;
    }

    public static function tableRecordRowRenderer($data, $pk, $index, GridView $grid){
        $result = [];

        if(!empty($data['__pk'])){
            $result['data-pk'] = $data['__pk'];
        }

        if(!empty($data['__record_new'])){
            $result += [
                'title' => 'новая запись',
                'class' => 'success record-insert',
            ];
        }
        elseif(!empty($data['__record_drop'])) {
            $result += [
                'title' => 'запись удалена',
                'class' => 'danger record-dropped',
            ];
        }
        elseif(!empty($data['__record_modify'])) {
            $result += [
                'title' => 'изменения в записи',
                'class' => 'record-modified',
            ];
        }

        if(!empty($result)){
            return $result;
        }
    }
}