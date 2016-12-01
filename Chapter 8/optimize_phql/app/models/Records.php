<?php

class Records extends Phalcon\Mvc\Model
{
    protected function initialize()
    {
        $this->hasMany('id', 'Links', 'recordsId', [
            'alias' => 'Links'
        ]);
    }
}
