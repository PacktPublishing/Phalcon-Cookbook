<?php

class Products extends Phalcon\Mvc\Model
{
    public $id;

    public $name;

    public function initialize()
    {
        $this->setConnectionService('dbClient');
    }
}
