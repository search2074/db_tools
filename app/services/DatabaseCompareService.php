<?php

namespace app\services;

use Yii;
use app\helpers\Db;
use yii\helpers\ArrayHelper;
use yii\data\ArrayDataProvider;
use app\services\DatabaseService;

class DatabaseCompareService
{
    /**
     * @var DatabaseService
     */
    private $leftDatabaseService;

    /**
     * @var DatabaseService
     */
    private $rightDatabaseService;

    /**
     * @var array
     */
    private $leftTables;

    /**
     * @var array
     */
    private $rightTables;

    /**
     * @var array
     */
    private $comparedTables = [
        'left_db' => [],
        'right_db' => [],
    ];

    /**
     * @var array
     */
    private $counters = [
        'left_db' => [
            'created_table' => 0,
            'edited_schema_table' => 0,
            'dropped_table' => 0,
        ],
        'right_db' => [
            'created_table' => 0,
            'edited_schema_table' => 0,
            'dropped_table' => 0,
        ],
    ];

    public function __construct(DatabaseService $leftDatabaseService, DatabaseService $rightDatabaseService){
        $this->leftDatabaseService = $leftDatabaseService;
        $this->rightDatabaseService = $rightDatabaseService;
    }

    public function compare(){
        $this->leftTables = $this->leftDatabaseService->getTablesInfo();
        $this->rightTables = $this->rightDatabaseService->getTablesInfo();
        $result = $this->compareTables();

        return $result;
    }

    /**
     * Compare two array of tables
     * @return array
     */
    private function compareTables(){
        $allTables = $this->leftTables + $this->rightTables;

        if(empty($allTables)){
            return [];
        }

        ksort($allTables);

        $leftTablesData = $this->getTablesData('left_db');
        $rightTablesData = $this->getTablesData('right_db');
        $leftChecksumTables = $this->leftDatabaseService->getChecksumTables();
        $rightChecksumTables = $this->rightDatabaseService->getChecksumTables();

        foreach ($allTables as $table => $fields) {
            $allTableColumns = [];
            $leftTableColumns = $rightTableColumns = [];

            if(!empty($this->leftTables[$table])){
                $leftTableColumns = $this->leftTables[$table];

                // get left columns
                $allTableColumns += $leftTableColumns;
            }

            if(!empty($this->rightTables[$table])){
                $rightTableColumns = $this->rightTables[$table];

                // get right columns
                $allTableColumns += $rightTableColumns;
            }

            if(empty($allTableColumns)){
                continue;
            }

            if(!empty($leftTableColumns)){
                $this->comparedTables['left_db'][$table] = [
                    'created_table' => false,
                    'edited_schema_table' => false,
                    'edited_table_data' => false,
                    'table_data_diff' => [],
                    'dropped_table' => false,
                    'columns' => $leftTableColumns,
                    'columns_diff' => [],
                ];

                if(empty($rightTableColumns)){
                    $this->comparedTables['left_db'][$table]['created_table'] = true;
                }
                else {
                    // echo "diff table {$table} for left and right db\n";
                    $columnsDiff = $this->getColumnsDiff(
                        $leftTableColumns,
                        $rightTableColumns,
                        $allTableColumns
                    );

                    if(!empty($columnsDiff)){
                        $this->comparedTables['left_db'][$table]['edited_schema_table'] = true;
                        $this->comparedTables['left_db'][$table]['columns_diff'] = $columnsDiff;
                    }
                }

                // diff data
                if(!empty($leftTablesData[$table]) && !empty($rightTablesData[$table])){
                    $data_diff = array_diff($leftTablesData[$table], $rightTablesData[$table]);

                    $leftTableChecksum = empty($leftChecksumTables[$table]) ? 0 : $leftChecksumTables[$table];
                    $rightTableChecksum = empty($rightChecksumTables[$table]) ? 0 : $rightChecksumTables[$table];

                    if($leftTableChecksum != $rightTableChecksum){
                        $data_diff += [
                            'TABLE_CHECKSUM' => $rightTableChecksum
                        ];
                    }

                    if(!empty($data_diff)){
//                        var_dump($leftTablesData[$table]);
//                        var_dump($rightTablesData[$table]);
//
//                        var_dump($data_diff);
                        $this->comparedTables['left_db'][$table]['edited_table_data'] = true;
                        $this->comparedTables['left_db'][$table]['table_data_diff'] = $data_diff;
                    }
                }
            }

            if(!empty($rightTableColumns)){
                $this->comparedTables['right_db'][$table] = [
                    'created_table' => false,
                    'edited_table_data' => false,
                    'table_data_diff' => [],
                    'edited_schema_table' => false,
                    'dropped_table' => false,
                    'columns' => $rightTableColumns,
                    'columns_diff' => [],
                ];

                if(empty($leftTableColumns)){
                    $this->comparedTables['right_db'][$table]['dropped_table'] = true;
                }
                else {
                    // echo "diff table {$table} for right and left db\n";
                    $columnsDiff = $this->getColumnsDiff(
                        $rightTableColumns,
                        $leftTableColumns,
                        $allTableColumns
                    );

                    if(!empty($columnsDiff)){
                        $this->comparedTables['right_db'][$table]['edited_schema_table'] = true;
                        $this->comparedTables['right_db'][$table]['columns_diff'] = $columnsDiff;
                    }
                }

                // diff data
                if(!empty($leftTablesData[$table]) && !empty($rightTablesData[$table])){
                    $data_diff = array_diff($rightTablesData[$table], $leftTablesData[$table]);

                    $leftTableChecksum = empty($leftChecksumTables[$table]) ? 0 : $leftChecksumTables[$table];
                    $rightTableChecksum = empty($rightChecksumTables[$table]) ? 0 : $rightChecksumTables[$table];

                    if($leftTableChecksum != $rightTableChecksum){
                        $data_diff += [
                            'TABLE_CHECKSUM' => $leftTableChecksum
                        ];
                    }

                    if(!empty($data_diff)){
                        $this->comparedTables['right_db'][$table]['edited_table_data'] = true;
                        $this->comparedTables['right_db'][$table]['table_data_diff'] = $data_diff;
                    }
                }
            }

//            echo "left table:\n";
//            var_dump($leftTable);
//            echo "right table:\n";
//            var_dump($rightTable);
//            die;
//
//            var_dump($allTableColumns);
//            die;

//            echo "table: {$table}\n";
//            echo "fields: \n";
        }

//        var_dump($this->comparedTables);
//        die;
        return $this->comparedTables;
    }

    /**
     * Diff fields
     * @param $firstColumns
     * @param $secondColumns
     * @param $allColumns
     * @return array
     */
    private function getColumnsDiff($firstColumns, $secondColumns, $allColumns){
        if(empty($firstColumns)){
            return [];
        }

        $result = [];

        foreach ($allColumns as $column_name => $column_properties){
            $opts = [];

            // column added
            if(!empty($firstColumns[$column_name]) && empty($secondColumns[$column_name])){
                $opts['added_column'] = true;
            }

            // column dropped
            if(empty($firstColumns[$column_name]) && !empty($secondColumns[$column_name])){
                $opts['dropped_column'] = true;
            }

            // column edited
            if(!empty($firstColumns[$column_name]) && !empty($secondColumns[$column_name])){
                $edited_columns = array_diff($firstColumns[$column_name], $secondColumns[$column_name]);

                if(!empty($edited_columns)){
                    $opts['edited_column'] = true;
                    $opts['edited_columns'] = $edited_columns;
                }
            }

            if(!empty($opts)){
                $result[$column_name] = $opts;
            }
        }

        return $result;
    }

    private function getDataProvider($db = 'left_db'){
        $dataProvider = new ArrayDataProvider([
            'allModels' => $this->comparedTables[$db],
            'sort' => [
                'attributes' => ['title'],
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

    private function getCountTables($db = 'left_db', $type = 'created_table'){
        $tables = $this->comparedTables[$db];

        if(empty($tables)){
            return 0;
        }

        foreach ($tables as $table) {
            if($table[$type] === true){
                $this->counters[$db][$type]++;
            }
        }

        return $this->counters[$db][$type];
    }

    private function getTablesData($db){
        $tables = Yii::$app->$db->createCommand('
          SELECT 
            TABLE_CATALOG,
            TABLE_NAME,
            TABLE_TYPE,
            ENGINE,
            VERSION,
            ROW_FORMAT,
            TABLE_ROWS,
            /*AVG_ROW_LENGTH,*/
            /*DATA_LENGTH,*/
            MAX_DATA_LENGTH,
            INDEX_LENGTH,
            DATA_FREE,
            AUTO_INCREMENT,
            TABLE_COLLATION,
            TABLE_COMMENT
          FROM information_schema.TABLES 
          WHERE TABLE_CATALOG = "def" and TABLE_SCHEMA = :datebase
        ', [
            ':datebase' => DatabaseService::getDbName(Yii::$app->$db->dsn)
        ])->queryAll();

        return ArrayHelper::index($tables, function($row){ return $row['TABLE_NAME']; });
    }

    public function getLeftDbCountNewTables(){
        return $this->getCountTables('left_db', 'created_table');
    }

    public function getRightDbCountNewTables(){
        return $this->getCountTables('right_db', 'created_table');
    }

    public function getLeftDbCountDroppedTables(){
        return $this->getCountTables('left_db', 'dropped_table');
    }

    public function getRightDbCountDroppedTables(){
        return $this->getCountTables('right_db', 'dropped_table');
    }

    public function getLeftDbCountEditedSchemaTables(){
        return $this->getCountTables('left_db', 'edited_schema_table');
    }

    public function getRightDbCountEditedSchemaTables(){
        return $this->getCountTables('right_db', 'edited_schema_table');
    }

    public function getLeftDbTableDataDiff(){
        return $this->getDbTableDataDiff('left_db');
    }

    public function getRightDbTableDataDiff(){
        return $this->getDbTableDataDiff('right_db');
    }

    private function getDbTableDataDiff($db = 'left_db'){
        if(empty($this->comparedTables[$db])){
            return [];
        }

        $result = [];

        foreach ($this->comparedTables[$db] as $tableName => $comparedTable) {
            if(!empty($comparedTable['table_data_diff'])){
                $result[$tableName] = $comparedTable['table_data_diff'];
            }
        }

        return $result;
    }
}