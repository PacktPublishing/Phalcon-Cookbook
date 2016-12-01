<?php

$loader = new \Phalcon\Loader();
$loader->registerDirs([
    __DIR__ . '/../tasks',
    __DIR__ . '/../library'
]);
$loader->register();
