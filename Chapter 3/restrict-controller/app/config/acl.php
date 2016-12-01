<?php

$resources = [
    'index'    => ['index'],
    'session'  => ['index', 'signin', 'signout'],
    'profile'  => ['index'],
    'secret'   => ['data'],
];

$publicResources = [
    'index' => '*',
    'session' => '*'
];

$roles = [
    'Administrators' => [
        'profile'  => ['index'],
        'secret'   => ['data'],
    ],
    'Members' => [
        'profile'  => ['index']
    ],
    'Annonymous' => []
];

$acl = new Phalcon\Acl\Adapter\Memory();
$acl->setDefaultAction(Phalcon\Acl::DENY);

// Add all of the resources to the ACL
foreach ($resources as $controllerName => $actionList) {
    $acl->addResource(new Phalcon\Acl\Resource($controllerName), $actionList);
}

// Set the roles and allow specific access for actions
foreach ($roles as $roleName => $role) {
    $acl->addRole($roleName);
    foreach ($role as $controllerName => $actionList) {
        $acl->allow($roleName, $controllerName, $actionList);
    }
}

// Make public resources available to all roles
foreach ($publicResources as $controllerName => $actionList) {
    foreach ($roles as $roleName => $role) {
        $acl->allow($roleName, $controllerName, $actionList);
    }
}

return $acl;
