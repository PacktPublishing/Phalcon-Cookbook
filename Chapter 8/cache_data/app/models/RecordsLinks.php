<?php

class RecordsLinks extends Phalcon\Mvc\Model
{
    public function initialize()
    {
        // TODO: Ideally this would be done automatically from the hasManyToMany relation
        $this->belongsTo(
            'linksId',
            'Cache_results\Models\Links',
            'id'
        );
    }
}
