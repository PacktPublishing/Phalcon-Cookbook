<?php

class IndexController extends ControllerBase
{
    public function indexAction()
    {
        $sombrero = Hats::findFirstByName('sombrero');
        $porsche = Cars::findFirstByName('porsche');

        return $sombrero->name . ' - ' . $porsche->name;
    }
}
