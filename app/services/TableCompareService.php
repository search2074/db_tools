<?php

namespace app\services;

use Yii;
use yii\db\Exception;
use yii\data\ArrayDataProvider;
use yii\helpers\ArrayHelper;

/**
 * Compare table data service class
 * @package app\services
 */
class TableCompareService
{
    /**
     * @var DatabaseService
     */
    private $leftDatabaseService;

    /**
     * @var DatabaseService
     */
    private $rightDatabaseService;

    private $comparedData = [
        'left_db' => [],
        'right_db' => [],
        'left_diff' => [],
        'right_diff' => [],
        'pk' => '',
        'table_name' => '',
        'columns' => [],
    ];

    public function __construct(DatabaseService $leftDatabaseService, DatabaseService $rightDatabaseService){
        $this->leftDatabaseService = $leftDatabaseService;
        $this->rightDatabaseService = $rightDatabaseService;
    }

    /**
     * Get compared data from two tables
     * TODO implement $source_database
     * @param string $source_database Source database
     * @param string $table_name compared table
     * @return array
     * @throws Exception
     */
    public function compare($source_database, $table_name){
        $leftTableData = $this->getLeftTableData($table_name);
        $rightTableData = $this->getRightTableData($table_name);
        $diff = array_diff(array_map('json_encode', $leftTableData), array_map('json_encode', $rightTableData));
        $this->comparedData['left_diff'] = array_map('json_decode', $diff);
        $rightDiff = array_diff(array_map('json_encode', $rightTableData), array_map('json_encode', $leftTableData));
        $this->comparedData['right_diff'] = array_map('json_decode', $rightDiff);
        $leftPrimaryKey = $this->getLeftTablePrimaryKey($table_name);
        $rightPrimaryKey = $this->getRightTablePrimaryKey($table_name);

        if($leftPrimaryKey !== $rightPrimaryKey){
            throw new Exception('Primary keys is different');
        }

        $this->comparedData['pk'] = $leftPrimaryKey;
        $this->comparedData['table_name'] = $table_name;
        $differentIds = $this->getDifferentIds(
            $leftPrimaryKey,
            $this->comparedData['left_diff'],
            $this->comparedData['right_diff']
        );
        $leftDb = $this->getTableData('left_db', $leftPrimaryKey, $table_name, $differentIds);
        $rightDb = $this->getTableData('right_db', $rightPrimaryKey, $table_name, $differentIds);
        $this->comparedData['left_db'] = ArrayHelper::index(
            $leftDb,
            function($row)use($leftPrimaryKey){ return $row[$leftPrimaryKey]; }
        );
        $this->comparedData['right_db'] = ArrayHelper::index(
            $rightDb,
            function($row)use($rightPrimaryKey){ return $row[$rightPrimaryKey]; }
        );
        $this->comparedData['columns'] = [];

        if(!empty($leftDb)){
            $this->comparedData['columns'] = array_keys($leftDb[0]);
        }
        elseif(!empty($rightDb)){
            $this->comparedData['columns'] = array_keys($rightDb[0]);
        }

        $this->adaptationComparedData();

        return $this->comparedData;
    }

    private function adaptationComparedData(){
        $all = $this->comparedData['left_db'] + $this->comparedData['right_db'];

        foreach ($all as $pk => $row) {
            if(!empty($this->comparedData['left_db'][$pk])){
                $this->comparedData['left_db'][$pk]['__pk'] = $this->comparedData['pk'];
            }

            if(!empty($this->comparedData['right_db'][$pk])){
                $this->comparedData['right_db'][$pk]['__pk'] = $this->comparedData['pk'];
            }

            if(!empty($this->comparedData['left_db'][$pk]) && empty($this->comparedData['right_db'][$pk])){
                $this->comparedData['left_db'][$pk]['__record_new'] = true;
            }

            if(empty($this->comparedData['left_db'][$pk]) && !empty($this->comparedData['right_db'][$pk])){
                $this->comparedData['right_db'][$pk]['__record_drop'] = true;
            }

            if(!empty($this->comparedData['left_db'][$pk]) && !empty($this->comparedData['right_db'][$pk])){
                $modify = array_diff(
                    $this->comparedData['left_db'][$pk],
                    $this->comparedData['right_db'][$pk]
                );

                if(!empty($modify)){
                    $this->comparedData['left_db'][$pk]['__record_modify'] = true;
                    $this->comparedData['right_db'][$pk]['__record_modify'] = true;
                    $this->comparedData['left_db'][$pk]['__record_modify_values'] = $modify;
                    $this->comparedData['right_db'][$pk]['__record_modify_values'] = $modify;
                }
            }
        }
    }

    public function getLeftTablePrimaryKey($table_name){
        return $this->getPrimaryKey('left_db', $table_name);
    }

    public function getRightTablePrimaryKey($table_name){
        return $this->getPrimaryKey('right_db', $table_name);
    }

    private function getPrimaryKey($db = 'left_db', $table_name){
        if($db === 'left_db'){
            $db = $this->leftDatabaseService->db;
        }
        elseif($db === 'right_db') {
            $db = $this->rightDatabaseService->db;
        }

        $dbName = DatabaseService::getDbName(Yii::$app->$db->dsn);

        return Yii::$app->$db->createCommand("
            SELECT COLUMN_NAME 
            FROM  information_schema.`COLUMNS` 
            WHERE `TABLE_SCHEMA` = :DB_TABLE_SCHEMA and `TABLE_NAME` = :DB_TABLE_NAME and COLUMN_KEY = 'pri'
        ", [
            ':DB_TABLE_SCHEMA' => $dbName,
            ':DB_TABLE_NAME' => $table_name
        ])->queryScalar();
    }

    /**
     * @param string $pk Primary key
     * @param \stdClass[] $left_compared_data
     * @return array
     */
    private function getDifferentIds($pk, array $left_compared_data, array $right_compared_data){
        if(empty($left_compared_data) && empty($right_compared_data)){
            return [];
        }

        $result = [];

        foreach ($left_compared_data as $value) {
            if(empty($value->$pk)){
                continue;
            }

            $result[] = $value->$pk;
        }

        foreach ($right_compared_data as $value) {
            if(empty($value->$pk)){
                continue;
            }

            $result[] = $value->$pk;
        }

        return $result;
    }

    private function getTableData($db, $pk, $table_name, $ids){
        if($db === 'left_db'){
            $db = $this->leftDatabaseService->db;
        }
        elseif($db === 'right_db') {
            $db = $this->rightDatabaseService->db;
        }

        return Yii::$app->$db->createCommand("
            SELECT * 
            FROM {$table_name}
            WHERE {$pk} in ('" . implode("','", $ids) . "')
        ")->queryAll();
    }

    public function getLeftTableData($table_name){
        $db = $this->leftDatabaseService->db;

        return Yii::$app->$db->createCommand("SELECT * FROM {$table_name}")->queryAll();
    }

    public function getRightTableData($table_name){
        $db = $this->rightDatabaseService->db;

        return Yii::$app->$db->createCommand("SELECT * FROM {$table_name}")->queryAll();
    }

    private function getCompareTablesDataQueryDeprecated($source_database, $table_name){
        $leftDb = $this->leftDatabaseService->db;
        $rightDb = $this->rightDatabaseService->db;
        $leftDbName = DatabaseService::getDbName(Yii::$app->$leftDb->dsn);
        $rightDbName = DatabaseService::getDbName(Yii::$app->$rightDb->dsn);

        $sourceColumns = $this->leftDatabaseService->getTableColumns('car_trim');
        $destinationColumns = $this->rightDatabaseService->getTableColumns('car_trim');

        if(empty($sourceColumns) || empty($destinationColumns)){
            return null;
        }

        $firstConditions = [];

        foreach ($sourceColumns as $sourceColumn) {
            if(in_array($sourceColumn, $destinationColumns)){
                $firstConditions[] = "t1.{$sourceColumn} NOT IN (SELECT {$sourceColumn} FROM {$rightDbName}.{$table_name})";
            }
        }

        if(empty($firstConditions)){
            return null;
        }

        $secondConditions = [];

        foreach ($destinationColumns as $destinationColumn) {
            if(in_array($destinationColumn, $sourceColumns)){
                $secondConditions[] = "t2.{$destinationColumn} NOT IN (SELECT {$destinationColumn} FROM {$leftDbName}.{$table_name})";
            }
        }

        if(empty($secondConditions)){
            return null;
        }

        $firstSelect = "'{$leftDbName}' database_name, t1.*";
        $firstFrom = "{$leftDbName}.car_trim t1";
        $firstWhere = implode(' or ', $firstConditions);
        $secondSelect = "'{$rightDbName}' database_name, t2.*";
        $secondFrom = "{$rightDbName}.{$table_name} t2";
        $secondWhere = implode(' or ', $secondConditions);
        return "
            SELECT {$firstSelect}
            FROM {$firstFrom}
            WHERE {$firstWhere}
            UNION
            SELECT {$secondSelect}
            FROM {$secondFrom}
            WHERE {$secondWhere} 
        ";
    }

    /**
     * @return array
     */
    public function getComparedData($index = null)
    {
        if(!empty($this->comparedData[$index])){
            return $this->comparedData[$index];
        }

        return $this->comparedData;
    }

    private function getDataProvider($db = 'left_db'){
        $dataProvider = new ArrayDataProvider([
            'allModels' => $this->getComparedData($db),
            'sort' => [
                'attributes' => [$this->comparedData['pk']],
            ],
            'pagination' => [
                'pageSize' => 500,
            ],
        ]);

        return $dataProvider;
    }

    public function getLeftDbDataProvider(){
        return $this->getDataProvider('left_db');
    }

    public function getRightDbDataProvider(){
        return $this->getDataProvider('right_db');
    }

    public function hasChanges($db = 'left_db'){
        return !empty($this->getLeftDbDataProvider()->getModels()[$db])
            && count($this->getLeftDbDataProvider()->getModels()[$db]) > 0;
    }
}