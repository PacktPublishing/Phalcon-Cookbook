<?php

class UsersController extends Phalcon\Mvc\Controller
{
    public function indexAction()
    {

    }

    public function createAction()
    {
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        $user = new Users([
            'username' => $username,
            'password' => $password
        ]);
        if(!$user->create()) {
            $this->flash->error($user->getMessages()[0]);
            return;
        }

        $this->flash->success('User created');
    }

    public function changePasswordAction()
    {
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        $user = Users::findFirstByUsername($username);
        if (!$user) {
            $this->flash->error('The user could not be found.');
            return;
        }

        $user->password = $password;
        if(!$user->save()) {
            $this->flash->error($user->getMessages()[0]);
            return;
        }

        $this->flash->success('Password Changed successfully.');
    }

    public function signinAction()
    {
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        $user = Users::findFirstByUsername($username);
        if (!$user) {
            $this->flash->error('The user could not be found.');
            return;
        }

        if (!$this->security->checkHash($password, $user->password)) {
            $this->flash->error('Invalid password.');
            return;
        }

        $this->flash->success('Signed in successfully.');
    }

}
