# db_tools
Mysql database tools. Compare database, table schema, table data and sync.

# description
1. Transfer tables between databases
    * select new tables from left:
    ![Alt text](/docs/images/table_transfer/1.png?raw=true "select tables")
    * and click to "Start process" button:
    ![Alt text](/docs/images/table_transfer/2.png?raw=true "click start process")
2. Transfer tables data between databases
    * click to "показать изменения":
    ![Alt text](/docs/images/table_data_transfer/1.png?raw=true "view edit")
    * select rows in modal window and click to "Start process" button:
    ![Alt text](/docs/images/table_data_transfer/2.png?raw=true "click start process")
3. Update tables data between databases
    * select rows in modal window and click to "Start process" button:
    ![Alt text](/docs/images/table_data_update/1.png?raw=true "click start process")
4. Update tables fields:
    * select tables from left and click to "Start process" button:
    ![Alt text](/docs/images/table_fields_modify/1.png?raw=true "select tables and click start process")
4. Delete tables data between databases
    * select rows in modal window and click to "Start process" button:
    ![Alt text](/docs/images/table_data_delete/1.png?raw=true "click start process")
5. Delete tables:
    * select tables from right:
    ![Alt text](/docs/images/table_delete/1.png?raw=true "select tables and click start process")

# configs
Put configuration.php file to /app/config.
Example:
```php
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
    'params' => [
        'user' => [
            '100' => [
                'id' => '100',
                'username' => 'admin',
                'password' => 'admin',
                'authKey' => 'test100key',
                'accessToken' => '100-token',        
            ],
        ],
        // if app located in folder
        // 'baseUrl' => '/db-tools',
        // tables settings
        'tables_settings' => [
            // for ignored tables
            'ignore' => [
                //'table_name',
            ],
            // table groups
            'groups' => [
                //'table_name',          
            ],
        ],
    ],
    // run actions after db sync/update
    'post_actions' => [
        'exec /var/www/your_script.sh'
    ],
];
```

# Nginx configs
App in separate directory:
```
server {
    listen  80;
	server_name site.com;

	root /var/www/site.com;
	index index.php index.html index.htm;
	
	# separate location 
	location /db-tools {
        alias /var/www/db_tools/app/web;
        index /db_tools/app/web/index.php;
        try_files $uri $uri/ /db_tools/app/web/index.php?$args;
    }
}
```