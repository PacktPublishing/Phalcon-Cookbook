<?php

class DispatcherException extends Phalcon\Mvc\User\Plugin
{

    public function beforeException(Phalcon\Events\Event $event, Phalcon\Mvc\Dispatcher $dispatcher, $exception)
    {
        switch ($exception->getCode()) {
            case Phalcon\Mvc\Dispatcher::EXCEPTION_HANDLER_NOT_FOUND:
            case Phalcon\Mvc\Dispatcher::EXCEPTION_ACTION_NOT_FOUND:
                $dispatcher->forward([
                    'controller' => 'error',
                    'action'     => 'show404',
                ]);
                return false;
                break;
        }

        $dispatcher->forward([
            'controller' => 'error',
            'action'     => 'unhandledException',
        ]);
        return false;
    }

}
