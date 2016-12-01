<?php

class Hats extends Phalcon\Mvc\Model
{
    public function initialize()
    {
        $this->hasManyToMany(
            "id",
            "HatsColors",
            "hats_id",
            "colors_id",
            "Colors",
            "id",
            [
                'alias' => 'colors'
            ]
        );
    }
}
