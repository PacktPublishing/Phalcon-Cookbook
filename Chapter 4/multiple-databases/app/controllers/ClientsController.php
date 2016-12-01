<?php

class ClientsController extends Phalcon\Mvc\Controller
{

    public function indexAction()
    {
        $clientName = $this->request->getQuery('name', null, 'Bob');

        $client = Clients::findFirstByName($clientName);
        if (!$client) {
            return '<p>The client could not be found.</p>';
        }
        $client->select();

        $products = Products::find();

        $this->response->appendContent('<h3>Products</h3><ul>');
        foreach ($products as $product) {
            $this->response->appendContent('<li>' . $product->name . '</li>');
        }
        $this->response->appendContent('</ul>');

        $this->response->send();
    }

}
