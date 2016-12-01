<?php

$loader = new \Phalcon\Loader();
$loader->registerDirs([
    __DIR__ . '/../tasks',
    __DIR__ . '/../models',
]);
$loader->register();
