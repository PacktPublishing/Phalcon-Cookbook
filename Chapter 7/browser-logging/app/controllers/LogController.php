<?php

class LogController extends ControllerBase
{

    public function indexAction()
    {
        $logger = $this->getDI()
            ->getLogger();

        $logger->debug('Debug Log');
        $logger->log('Normal Log, Same as Debug Log');
        $logger->info('Info Log');
        $logger->warning('Warning Log');
        $logger->error('Error Log');
        $logger->critical('Critical Log');

        $this->response->appendContent('Check your Firebug Logger Console for messages.');
        $this->response->send();
    }

}
