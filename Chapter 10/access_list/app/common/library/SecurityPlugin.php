<?php
namespace AccessList;

class SecurityPlugin extends \Phalcon\Mvc\User\Plugin
{
    public function beforeExecuteRoute(\Phalcon\Events\Event $event, \Phalcon\Mvc\Dispatcher $dispatcher)
    {
        $acl = $this->getDI()
            ->getAcl();
        $request = $this->getDI()
            ->getRequest();
        $router = $this->getDI()
            ->getRouter();

        $role = ($request->hasQuery('role')) ? $request->getQuery('role') : 'Anonymous';
        $controllerName = $dispatcher->getControllerName();
        $actionName = $dispatcher->getActionName();
        $moduleName = $router->getModuleName();

        $resourceName = "$moduleName::$controllerName";

        if (!$acl->isAllowed($role, $resourceName, $actionName)) {
            echo "Not allowed!";
            exit;
        }
    }
}
