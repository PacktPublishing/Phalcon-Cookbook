<?php

use Phalcon\Loader;

$loader = new Loader();

/**
 * Register Namespaces
 */
$loader->registerNamespaces([
    'AccessList\Models' => APP_PATH . '/common/models/',
    'AccessList'        => APP_PATH . '/common/library/',
]);

/**
 * Register module classes
 */
$loader->registerClasses([
    'AccessList\Modules\Frontend\Module' => APP_PATH . '/modules/frontend/Module.php',
    'AccessList\Modules\Cli\Module'      => APP_PATH . '/modules/cli/Module.php'
]);

$loader->register();

require BASE_PATH . '/vendor/autoload.php';
