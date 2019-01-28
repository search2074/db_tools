<?php

namespace app\services;

use Yii;
use yii\data\ArrayDataProvider;
use yii\db\Exception;

class DatabaseService
{
    public $db;

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
        if (preg_match('/dbname=([^;]*)/', $dsn, $match)) {
            return $match[1];
        } else {
            return null;
        }
    }

    public function getDataProvider(){
        $tables = $this->getTables();

        $dataProvider = new ArrayDataProvider([
            'allModels' => $tables,
            'sort' => [ // подключаем сортировку
                'attributes' => ['title'],
            ],
            'pagination' => [ //постраничная разбивка
                'pageSize' => 500,
            ],
        ]);

        return $dataProvider;
    }
}