<?php

namespace app\services;

use Yii;
use app\helpers\Db;
use yii\db\Exception;
use yii\data\ArrayDataProvider;

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
}