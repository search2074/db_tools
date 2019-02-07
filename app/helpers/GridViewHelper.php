<?php

namespace app\helpers;

use yii\helpers\Html;

class GridViewHelper {
    public static function dbTableColumnRenderer($data, $table_name){
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

        return $result;
    }
}