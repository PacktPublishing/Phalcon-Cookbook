<?php

class StartController extends ControllerBase
{

    public function indexAction()
    {
        return $this->dispatcher->forward([
            'controller' => 'handle',
            'action'     => 'forward'
        ]);
    }

    public function redirectAction()
    {
        return $this->dispatcher->forward([
            'controller' => 'handle',
            'action'     => 'redirect'
        ]);
    }

}
