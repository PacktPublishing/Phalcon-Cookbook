<?php
namespace AccessList\Modules\Cli\Tasks;

class BuildTask extends \Phalcon\Cli\Task
{
    public function mainAction()
    {
        $acl = $this->getDI()
            ->getAcl();

        $acl->addRole(new \Phalcon\Acl\Role('Admin'));
        $acl->addRole(new \Phalcon\Acl\Role('User'));
        $acl->addRole(new \Phalcon\Acl\Role('Anonymous'));

        $acl->addResource('frontend::index', ['index']);
        $acl->addResource('frontend::products', ['index', 'change', 'add', 'cart']);

        $acl->allow('Admin', 'frontend::products', '*');
        $acl->allow('Admin', 'frontend::index', '*');

        $acl->allow('User', 'frontend::products', ['index', 'cart']);
        $acl->allow('User', 'frontend::index', '*');

        $acl->allow('Anonymous', 'frontend::index', '*');
    }
}
