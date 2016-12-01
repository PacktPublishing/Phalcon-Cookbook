<?php

use Phalcon\Loader;

$loader = new Loader();

/**
 * Register Namespaces
 */
$loader->registerNamespaces([
    'VoltCompile\Models' => APP_PATH . '/common/models/',
    'VoltCompile' => APP_PATH . '/common/library/'
]);

$loader->registerClasses([
    'VoltCompile\Modules\Frontend\Module' => APP_PATH . '/modules/frontend/Module.php',
    'VoltCompile\Modules\Cli\Module'      => APP_PATH . '/modules/cli/Module.php'
]);

$loader->register();
