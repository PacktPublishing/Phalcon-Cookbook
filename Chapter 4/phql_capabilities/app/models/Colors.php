<?php

class Colors extends Phalcon\Mvc\Model
{
    public function initialize()
    {
        $this->hasManyToMany(
            "id",
            "HatsColors",
            "colors_id",
            "hats_id",
            "Hats",
            "id",
            [
                'alias' => 'hats'
            ]
        );
    }
}
