<?php

class DispatcherSecurity extends Phalcon\Mvc\User\Plugin
{

    public function beforeExecuteRoute(Phalcon\Events\Event $event, Phalcon\Mvc\Dispatcher $dispatcher)
    {
        $roleName = ($this->session->has('role')) ? $this->session->get('role') : 'Annonymous';
        $controllerName = $dispatcher->getControllerName();
        $actionName = $dispatcher->getActionName();

        if (!$this->acl->isAllowed($roleName, $controllerName, $actionName)) {
            $this->response->setContent('Unauthorized access.');
            $this->response->send();

            return false;
        }
    }

}
