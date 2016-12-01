<?php

class ProfileController extends \Phalcon\Mvc\Controller
{

    public function indexAction()
    {
        return 'Only signed in users should be able to change their profile';
    }
    
}
