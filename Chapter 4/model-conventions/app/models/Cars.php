<?php

class Cars extends Phalcon\Mvc\Model
{
    public function getSource()
    {
        return "cars_legacy123";
    }

    public function columnMap()
    {
        return [
            'id'             => 'id',
            'name_legacy123' => 'name'
        ];
    }
}
