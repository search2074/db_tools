<?php

namespace app\services;

use Yii;
use app\helpers\Db;
use yii\db\Exception;
use yii\data\ArrayDataProvider;
use yii\helpers\ArrayHelper;

class DatabaseService
{
    public $db;
    /**
     * @var ArrayDataProvider
     */
    public $dataProvider;

    /**
     * DatabaseService constructor.
     * @param $db
     * @throws Exception
     */
    public function __construct($db)
    {
        if(!$db){
            throw new Exception('Db not set');
        }

        $this->db = $db;
    }

    public function getTables(){
        $db = $this->db;

        return Yii::$app->$db->createCommand('SHOW TABLES')->queryColumn();
    }

    public static function getDbName($dsn){
        return Db::getDsnAttribute('dbname', $dsn);
    }

    /**
     * @return ArrayDataProvider
     */
    public function getDataProvider(){
        $tables = $this->getTables();

        $this->dataProvider = new ArrayDataProvider([
            'allModels' => $tables,
            'sort' => [
                'attributes' => ['title'],
            ],
            'pagination' => [
                'pageSize' => 500,
            ],
        ]);

        return $this->dataProvider;
    }

    /**
     * Compare with other data provider
     * @param ArrayDataProvider $comparedDataProvider
     * @return array
     */
    public function getDiff(ArrayDataProvider $comparedDataProvider){
        $diff = array_diff(
            $this->dataProvider->getModels(),
            $comparedDataProvider->getModels()
        );

        return [
            'new_tables' => $diff
        ];
    }

    public function getTablesInfo(){
        $db = $this->db;

        $table_schema = Yii::$app->$db
            ->createCommand('
              SELECT cols.* 
              FROM information_schema.COLUMNS cols 
              WHERE cols.TABLE_CATALOG = :table_catalog and cols.TABLE_SCHEMA = :table_schema
            ', [
                ':table_catalog' => 'def',
                ':table_schema' => self::getDbName(Yii::$app->$db->dsn)
            ])->queryAll();

        if(empty($table_schema)){
            return [];
        }

        $tables = [];

        foreach ($table_schema as $item) {
            if(empty($item['TABLE_SCHEMA'])){
                continue;
            }

            if(empty($tables[$item['TABLE_NAME']]) && !is_array($tables[$item['TABLE_NAME']])){
                $tables[$item['TABLE_NAME']] = [];
            }

            $tables[$item['TABLE_NAME']][$item['COLUMN_NAME']] = [
                'COLUMN_NAME' => $item['COLUMN_NAME'],
                'ORDINAL_POSITION' => $item['ORDINAL_POSITION'],
                'COLUMN_DEFAULT' => $item['COLUMN_DEFAULT'],
                'IS_NULLABLE' => $item['IS_NULLABLE'],
                'DATA_TYPE' => $item['DATA_TYPE'],
                'CHARACTER_MAXIMUM_LENGTH' => $item['CHARACTER_MAXIMUM_LENGTH'],
                'CHARACTER_OCTET_LENGTH' => $item['CHARACTER_OCTET_LENGTH'],
                'NUMERIC_PRECISION' => $item['NUMERIC_PRECISION'],
                'NUMERIC_SCALE' => $item['NUMERIC_SCALE'],
                'DATETIME_PRECISION' => $item['DATETIME_PRECISION'],
                'CHARACTER_SET_NAME' => $item['CHARACTER_SET_NAME'],
                'COLLATION_NAME' => $item['COLLATION_NAME'],
                'COLUMN_TYPE' => $item['COLUMN_TYPE'],
                'COLUMN_KEY' => $item['COLUMN_KEY'],
                'EXTRA' => $item['EXTRA'],
                'COLUMN_COMMENT' => $item['COLUMN_COMMENT'],
            ];
        }

        return $tables;
    }

    public function getTableColumns($table_name){
        $db = $this->db;

        $columns = Yii::$app->$db
            ->createCommand('
              SELECT cols.TABLE_CATALOG, cols.TABLE_SCHEMA, cols.TABLE_NAME, cols.COLUMN_NAME
              FROM information_schema.COLUMNS cols 
              WHERE cols.TABLE_CATALOG = :table_catalog and cols.TABLE_SCHEMA = :table_schema and cols.TABLE_NAME = :db_table_name
            ', [
                ':table_catalog' => 'def',
                ':table_schema' => self::getDbName(Yii::$app->$db->dsn),
                ':db_table_name' => $table_name,
            ])->queryAll();

        if(empty($columns)){
            return [];
        }

        return ArrayHelper::getColumn($columns, function($item){ return $item['COLUMN_NAME']; });
    }

    /**
     * Prepare sql queries for records
     * @param $table_name
     * @param $records
     * @return mixed
     */
    public function prepareSqlForTableRecords($table_name, $records){
        $db = $this->db;

        foreach ($records as $i => $record) {
            if(in_array($record['operation'], ['update', 'insert', 'drop'])){
                $data = Yii::$app->$db
                    ->createCommand("
                      SELECT *
                      FROM `{$table_name}` 
                      WHERE `".$record['pk']."` = :num
                    ", [
                        ':num' => $record['num'],
                    ])->queryOne();

                if(!empty($data)){
                    $records[$i]['data'] = $data;

                    if($this->db === 'left_db' && $record['operation'] === 'insert'){
                        $columns = array_keys($data);
                        $records[$i]['sql'] = "INSERT INTO `{$table_name}` (`" . implode('`,`', $columns) . "`) VALUES ('" .
                            implode("','", $data) . "');";
                    }
                    else if($this->db === 'left_db' && $record['operation'] === 'update'){
                        $fields = [];

                        foreach ($data as $column_name => $column_value) {
                            $fields[] = "`{$column_name}` = '{$column_value}'";
                        }

                        $records[$i]['sql'] = "UPDATE `{$table_name}` SET " . implode(', ', $fields) . " WHERE `" .
                            $record['pk'] . "` = '".$record['num']."';";
                    }
                    else if($this->db === 'right_db' && $record['operation'] === 'drop'){
                        $records[$i]['sql'] = "DELETE FROM `{$table_name}` WHERE `" . $record['pk'] . "` = '".$record['num']."';";
                    }
                }
            }
        }

        return $records;
    }

    /**
     * @param $records
     * @return bool
     * @throws Exception
     */
    public function processTableData($records){
        if(empty($records)){
            throw new Exception("Empty data");
        }

        $db = $this->db;

        foreach ($records as $record) {
            if(!empty($record['sql'])){
//                echo "db: {$db}, query: ".$record['sql']."\n";continue;
                $result = Yii::$app->$db->createCommand($record['sql'])->execute();

                if(!$result){
                    throw new Exception("Error executing query: " . $record['sql']);
                }
            }
        }

        return true;
    }

    public function analyzeTables(){
        $tables = $this->getTables();

        if(empty($tables)){
            return $this;
        }

        $db = $this->db;
        Yii::$app->$db->createCommand("ANALYZE TABLE `" . implode('`,`', $tables) . "`;")->execute();

        return $this;
    }
}