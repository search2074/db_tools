<?php

$params = [
    'adminEmail' => 'admin@example.com',
];

if(file_exists(__DIR__ . '/configuration.php')){
    $configuration = require __DIR__ . '/configuration.php';

    if(!empty($configuration['users'])){
        $params['users'] = $configuration['users'];
    }
}

return $params;