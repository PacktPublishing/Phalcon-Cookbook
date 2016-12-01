<?php

class SecretController extends \Phalcon\Mvc\Controller
{

    public function dataAction()
    {
        return 'Only very important users should be able to access Secret Data.';
    }

}
