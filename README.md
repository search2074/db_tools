# db_tools
databases_tool

# configs
Put configuration.php file to /app/config.
Example:
```
<?php

return [
    'left_db' => [
        'host' => 'localhost',
        'db_name' => 'db_left',
        'username' => 'my_user_for_left_db',
        'password' => 'pass_for_user_left_db',
        'charset' => 'utf8',
        'enableSchemaCache' => false,
    ],
    'right_db' => [
        'host' => 'localhost',
        'db_name' => 'db_right',
        'username' => 'my_user_for_right_db',
        'password' => 'pass_for_user_right_db',
        'charset' => 'utf8',
        'enableSchemaCache' => false,
    ],
    'user' => [
        '100' => [
            'id' => '100',
            'username' => 'admin',
            'password' => 'admin',
            'authKey' => 'test100key',
            'accessToken' => '100-token',        
        ],
    ],
];
```