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

            if(in_array($item['TABLE_NAME'], Yii::$app->params['tables_settings']['ignore'])){
                continue;
            }

            if(empty($tables[$item['TABLE_NAME']])){
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

    public function getTableShowColumns($table_name){
        $db = $this->db;

        $columns = Yii::$app->$db
            ->createCommand("SHOW COLUMNS FROM `{$table_name}`")->queryAll();

        if(empty($columns)){
            return [];
        }

        $data = [];

        foreach ($columns as $column){
            if(strstr($column['Type'], 'int')){
                $column['field_type'] = 'number';
            }
            else {
                $column['field_type'] = 'text';
            }

            $data[$column['Field']] = $column;
        }

        return $data;
    }

    public function getTableDataFromId($table_name, $pk, $id){
        $db = $this->db;

        $data = Yii::$app->$db
            ->createCommand("
                      SELECT *
                      FROM `{$table_name}` 
                      WHERE `{$pk}` = :num
                    ", [
                ':num' => $id,
            ])->queryOne();

        return $data;
    }

    private function clearValue($value){
        if(empty($value)){
            return $value;
        }

        $value = preg_replace('/(\'{1,})/i', "''", $value);
        $value = preg_replace('/(\\\"{1,})/i', '"', $value);

        return $value;
    }

    /**
     * Prepare sql queries for records
     * @param $table_name
     * @param $records
     * @return mixed
     */
    public function prepareSqlForTableRecords($table_name, $records){
        $metaTableColumns = $this->getTableShowColumns($table_name);

        foreach ($records as $i => $record) {
            if(in_array($record['operation'], ['update', 'insert', 'drop'])){
                $data = $this->getTableDataFromId($table_name, $record['pk'], $record['num']);

                if(!empty($data)){
                    $records[$i]['data'] = $data;

                    if($this->db === 'left_db' && $record['operation'] === 'insert'){
                        $values = [];
                        foreach ($data as $field => $value) {
                            if($value === null) {
                                $values[] = "NULL";
                            }
                            else {
                                $value = addslashes($value);
                                $values[] = "'{$value}'";
                            }
                        }

                        $columns = array_keys($data);
                        $records[$i]['sql'] = "INSERT INTO `{$table_name}` (`" . implode('`,`', $columns) . "`) VALUES (" .
                            implode(",", $values) . ");";
                    }
                    else if($this->db === 'left_db' && $record['operation'] === 'update'){
                        $fields = [];

                        foreach ($data as $column_name => $column_value) {
                            $metaTableColumn = $metaTableColumns[$column_name];
                            $column_value = $this->clearValue($column_value);

                            // null
                            if(is_null($column_value)){
                                $fields[] = "`{$column_name}` = NULL";
                            }
                            // int
                            else if(isset($metaTableColumn['field_type']) && $metaTableColumn['field_type'] === 'number'){
                                $fields[] = "`{$column_name}` = {$column_value}";
                            }
                            // string
                            else {
                                $column_value = addslashes($column_value);
                                //$column_value = addcslashes($column_value, '"\\/');
                                $fields[] = "`{$column_name}` = '{$column_value}'";
                            }
                        }

                        $records[$i]['sql'] = "UPDATE `{$table_name}` SET " . implode(',', $fields) . " WHERE `" .
                            $record['pk'] . "` = '".$record['num']."';";
//                        var_dump($records[$i]['sql']);
//                        die;
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
                try {
                    Yii::$app->$db->createCommand($record['sql'])->execute();
                }
                catch (\Exception $e) {
                    throw new Exception("Error executing query: " . $e->getMessage());
                }
//                echo "db: {$db}, query: ".$record['sql']."\n";continue;
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

    public function optimizeTables($table = null){
        $db = $this->db;

        if($table){
            Yii::$app->$db->createCommand("OPTIMIZE TABLE `{$table}`;")->execute();

            return $this;
        }

        $tables = $this->getTables();

        if(empty($tables)){
            return $this;
        }

        Yii::$app->$db->createCommand("OPTIMIZE TABLE `" . implode('`,`', $tables) . "`;")->execute();

        return $this;
    }

    public function getChecksumTables(){
        $tables = $this->getTables();

        if(empty($tables)){
            return [];
        }

        $db = $this->db;
        $items = Yii::$app->$db->createCommand("CHECKSUM TABLE `" . implode('`,`', $tables) . "`;")->queryAll();

        if(empty($items)){
            return [];
        }

        $result = [];
        $dbName = self::getDbName(Yii::$app->$db->dsn);

        foreach ($items as $item) {
            $str = str_replace("{$dbName}.", '', $item['Table']);

            if($str){
                $result[$str] = $item['Checksum'];
            }
        }

        return $result;
    }

    public function repairTables($table = null){
        $db = $this->db;

        if($table){
            Yii::$app->$db->createCommand("REPAIR TABLE `{$table}`;")->execute();

            return $this;
        }

        $tables = $this->getTables();

        if(empty($tables)){
            return $this;
        }

        Yii::$app->$db->createCommand("REPAIR TABLE `" . implode('`,`', $tables) . "`;")->execute();

        return $this;
    }

    private function isJson($string) {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}