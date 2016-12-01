<?php
namespace AccessList\Modules\Frontend\Controllers;

class ProductsController extends \Phalcon\Mvc\Controller
{
    public function indexAction()
    {
        return 'Accessed Products Index';
    }

    public function changeAction()
    {
        return 'Accessed Products Change';
    }

    public function addAction()
    {
        return 'Accessed Products Add';
    }

    public function cartAction()
    {
        return 'Accessed Products Cart';
    }
}
