<?php

class SigninController extends ControllerBase
{
    public function indexAction()
    {
        if ($this->request->isPost()) {
            if ($this->security->checkToken()) {
                $this->flash->success('Succesfully signed in.');
            } else {
                $this->flash->error('CSRF token did not match.');
            }
        }
    }
}
