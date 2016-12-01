<?php

class HandleController extends ControllerBase
{

    public function forwardAction()
    {
      return $this->dispatcher->forward([
          'controller' => 'index',
          'action'     => 'index'
      ]);
    }

    public function redirectAction()
    {
        return $this->response->redirect('https://www.packtpub.com/', true);
    }

}
