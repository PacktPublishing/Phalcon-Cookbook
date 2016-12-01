<?php

use Phalcon\Mvc\Controller;

abstract class ControllerBase extends Controller
{
    public function beforeExecuteRoute($dispatcher)
    {
        $this->response->setHeader('X-Frame-Options', 'SAMEORIGIN');
    }
}
