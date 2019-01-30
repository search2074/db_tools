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
            'sort' => [ // подключаем сортировку
                'attributes' => ['title'],
            ],
            'pagination' => [ //постраничная разбивка
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
}