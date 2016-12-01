<?php

class SessionController extends \Phalcon\Mvc\Controller
{

    public function signinAsAdministratorsRoleAction()
    {
        $this->session->set('role', 'Administrators');

        return "You are now signed in with the 'Administrators' role.";
    }

    public function signinAsMembersRoleAction()
    {
        $this->session->set('role', 'Members');

        return "You are now signed in with the 'Members' role";
    }

    public function signoutAction()
    {
        $this->session->remove('role');

        return "You have successfully signed out.";
    }

}
