<?php

$params = [
    'adminEmail' => 'admin@example.com',
];

if(file_exists(__DIR__ . '/configuration.php')){
    $configuration = require __DIR__ . '/configuration.php';

    if(!empty($configuration['params'])){
        $params = array_merge($params, $configuration['params']);
    }
}

return $params;