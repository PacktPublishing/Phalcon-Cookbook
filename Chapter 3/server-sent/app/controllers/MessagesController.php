<?php

class MessagesController extends ControllerBase
{

    public function indexAction()
    {

    }

    public function retrieveAction()
    {
        $this->response->setHeader("Content-Type", "text/event-stream");
        $this->response->send();
        $this->view->disable();

        // Remove one level of output buffering
        ob_get_clean();

        $count = 0;
        while ($count < 3) {
            $count++;

            // Send the 'update' event
            echo "event: phalcon-message\n";
            echo 'data: {"time": "' . date(DATE_ISO8601) . '"}';
            echo "\n\n";

            ob_flush();
            flush();
            sleep(1);
        }
    }
}
