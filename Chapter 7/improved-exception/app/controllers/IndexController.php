<?php

class IndexController extends ControllerBase
{

    public function indexAction()
    {
        $itemBuilder = $this->modelsManager
            ->createBuilder(1, 2, 3);


        // $items = Items::find();
        // foreach ($items as $item) {
        //     $this->response->appendContent($item->name);
        // }
        // // throw new \Exception('test');

        $this->response->send();
        $this->view->disable();
    }

}
