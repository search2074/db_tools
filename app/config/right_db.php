<?php

$db_config = [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=localhost;dbname=db_prod',
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
    if(!empty($configuration['right_db'])){
        $db_config['dsn'] = 'mysql:host='.$configuration['right_db']['host'].';dbname='.$configuration['right_db']['db_name'];
        $db_config['username'] = $configuration['right_db']['username'];
        $db_config['password'] = $configuration['right_db']['password'];
        $db_config['charset'] = $configuration['right_db']['charset'];
        $db_config['enableSchemaCache'] = $configuration['right_db']['enableSchemaCache'];
    }
}

return $db_config;