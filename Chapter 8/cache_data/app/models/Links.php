<?php

class Links extends \Phalcon\Mvc\Model
{
    public function initialize()
    {
        $this->hasManyToMany(
            'id',
            'RecordsLinks',
            'linksId',
            'recordsId',
            'Records',
            'id',
            [
              'alias' => 'records',
            ]
        );
    }
}
