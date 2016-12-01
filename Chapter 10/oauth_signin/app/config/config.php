<?php

defined('BASE_PATH') || define('BASE_PATH', realpath('../..'));
defined('APP_PATH') || define('APP_PATH', BASE_PATH . '/app');

return new \Phalcon\Config([
    'services' => [
        'google' => [
            'clientId'     => '358686266409-60q9erbidjplmt7t4fb1jc1c9vqd1q0s.apps.googleusercontent.com',
            'clientSecret' => 'U8d_V4TR5D4eACCF5lF4JmQV'
        ]
    ],
    'database' => [
        'adapter'     => 'Mysql',
        'host'        => 'localhost',
        'username'    => 'root',
        'password'    => 'root',
        'dbname'      => 'oauth_signin',
        'charset'     => 'utf8',
    ],
    'application' => [
        'appDir'         => APP_PATH . '/',
        'controllersDir' => APP_PATH . '/controllers/',
        'modelsDir'      => APP_PATH . '/models/',
        'migrationsDir'  => APP_PATH . '/migrations/',
        'viewsDir'       => APP_PATH . '/views/',
        'pluginsDir'     => APP_PATH . '/plugins/',
        'libraryDir'     => APP_PATH . '/library/',
        'cacheDir'       => BASE_PATH . '/cache/',
        'baseUri'        => str_replace('/public/index.php', '/', $_SERVER['SCRIPT_NAME'])
    ]
]);
