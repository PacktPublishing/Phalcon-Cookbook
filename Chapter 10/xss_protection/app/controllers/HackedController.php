<?php

class HackedController extends Phalcon\Mvc\Controller
{
    protected function onConstruct()
    {
        $this->hack = "<script>confirm('Transfer $10,000 to Nigeria for miracle hair treatment.')</script>";
    }

    public function htmlAction()
    {
        $this->view->setVars([
            'secure' => $this->request->getQuery('secure') === 'yes',
            'post'   => $this->hack
        ]);
    }

    public function javascriptAction()
    {
        $this->view->setVars([
            'secure' => $this->request->getQuery('secure') === 'yes',
            'title'  => "';</script>{$this->hack}<script>var blah='"
        ]);
    }

    public function cssAction()
    {
        $this->view->setVars([
            'secure' => $this->request->getQuery('secure') === 'yes',
            'style'  => "\"> {$this->hack} <p id=\""
        ]);
    }
}
