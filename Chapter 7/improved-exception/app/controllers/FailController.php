<?php

class FailController extends \Phalcon\Mvc\Controller
{

    public function indexAction()
    {
        $itemBuilder = $this->modelsManager
            ->createBuilder(1, 2, 3);
    }
}
