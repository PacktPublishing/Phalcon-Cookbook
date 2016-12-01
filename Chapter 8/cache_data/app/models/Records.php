<?php

class Records extends Phalcon\Mvc\Model
{
    public function initialize()
    {
        // TODO: Ideally this would be done automatically from the hasManyToMany relation
        $this->hasMany(
            'id',
            'RecordsLinks',
            'linksId'
        );

        $this->hasManyToMany(
            'id',
            'RecordsLinks',
            'recordsId',
            'linksId',
            'Links',
            'id',
            [
              'alias' => 'links',
            ]
        );
    }
}
