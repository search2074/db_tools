<?php

$db_config = [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=localhost;dbname=db_test',
    'username' => 'user',
    'password' => 'pass',
    'charset' => 'utf8',

    // Schema cache options (for production environment)
    'enableSchemaCache' => false,
    //'schemaCacheDuration' => 60,
    //'schemaCache' => 'cache',
];

if(file_exists(__DIR__ . '/configuration.php')){
    $configuration = require __DIR__ . '/configuration.php';

    if(!empty($configuration['left_db'])){
        $db_config['dsn'] = 'mysql:host='.$configuration['left_db']['host'].';dbname='.$configuration['left_db']['db_name'];
        $db_config['username'] = $configuration['left_db']['username'];
        $db_config['password'] = $configuration['left_db']['password'];
        $db_config['charset'] = $configuration['left_db']['charset'];
        $db_config['enableSchemaCache'] = $configuration['left_db']['enableSchemaCache'];
    }
}

return $db_config;