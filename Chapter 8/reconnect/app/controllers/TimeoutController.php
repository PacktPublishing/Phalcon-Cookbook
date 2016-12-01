<?php

class TimeoutController extends Phalcon\Mvc\Controller
{
    public function indexAction()
    {
        $this->test();

        $this->response->send();
    }


    public function reconnectAction()
    {
        define('RECONNECT_AFTER_TIMEOUT', true);

        $this->test();

        $this->response->send();
    }

    protected function test()
    {
        try {
            Records::count();

            sleep(6);

            Records::count();
        } catch (\Exception $e) {
            $this->response->appendContent('<br><p>The reconnect failed.<br></p>');
            $this->response->appendContent('<p>' . $e->getMessage() . '</p>');
            return;
        }

        $this->response->appendContent('The timeout reconnection succeeded.');
    }

}
