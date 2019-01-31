<?php

namespace app\helpers;

use Yii;
use yii\helpers\FileHelper;

class Db
{
    /**
     * Import from table file to db
     * @param string $db Database name
     * @param string $table Table name
     * @return bool
     * @throws \yii\base\Exception
     * @throws \yii\db\Exception
     */
    public static function import($db, $table)
    {
        if(!$table){
            throw new \yii\base\InvalidArgumentException('Table not set');
        }

        if(!is_writable(FileHelper::normalizePath(Yii::getAlias("@dumps_dir")))){
            throw new \yii\base\Exception("Dumps directory is not writable");
        }

        $db = Yii::$app->$db;

        if (!$db) {
            throw new \yii\db\Exception("Not connection to database {$db}");
        }

        $filePath = FileHelper::normalizePath(Yii::getAlias("@dumps_dir/{$table}.sql"));

        $command = 'mysql --host=' . self::getDsnAttribute('host', $db->dsn) . ' --user=' .
            $db->username . ' --password=' . $db->password . ' ' .
            self::getDsnAttribute('dbname', $db->dsn) . ' < ' . $filePath;
        $output = $return_var = null;

        exec($command, $output, $return_var);

        if(file_exists($filePath)){
            if(!unlink($filePath)){
                throw new \yii\base\Exception("Error deleting file {$table}.sql");
            }
        }

        return !$return_var;
    }

    /**
     * Export table from db to file
     * @param string $db Database name
     * @param string $table Table name
     * @return bool
     * @throws \yii\base\Exception
     * @throws \yii\db\Exception
     */
    public static function export($db, $table)
    {
        if(!$table){
            throw new \yii\base\InvalidArgumentException("Table {$table} not set");
        }

        if(!is_writable(FileHelper::normalizePath(Yii::getAlias("@dumps_dir")))){
            throw new \yii\base\Exception("Dumps directory is not writable");
        }

        $filePath = FileHelper::normalizePath(Yii::getAlias("@dumps_dir/{$table}.sql"));

        if(file_exists($filePath)){
            if(!unlink($filePath)){
                throw new \yii\base\Exception("Error deleting file {$table}.sql");
            }
        }

        $db = Yii::$app->$db;

        if (!$db) {
            throw new \yii\db\Exception("Not connection to database {$db}");
        }

        $command = 'mysqldump --host=' . self::getDsnAttribute('host', $db->dsn) .
            ' --user=' . $db->username . ' --password=' . $db->password . ' ' .
            self::getDsnAttribute('dbname', $db->dsn) . ' ' . $table . ' --skip-add-locks > ' . $filePath;
        $output = $return_var = null;

        exec($command, $output, $return_var);

        return !$return_var;
    }

    /**
     * Get dsn attribute
     * @param $name
     * @param $dsn
     * @return null
     */
    public static function getDsnAttribute($name, $dsn){
        if (preg_match('/' . $name . '=([^;]*)/', $dsn, $match)) {
            return $match[1];
        } else {
            return null;
        }
    }
}