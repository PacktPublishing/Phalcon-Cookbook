
## Implementing an Alternative Access Control Lists

In this recipe we will learn how to extend the base Phalcon ACL with a more feature rich version from the Phalcon Incubator.  This powerful ACL class has built-in support for PDO databases and can even directly add, delete and modify the database to construct the ACL data through its API.  This recipe is a bit different in that we will only be importing the structure of the database without any specific entries to describe the permissions.  We will fill this data by first running a CLI task to generate our permissions using the Incubator ACL class and then we will test them using the web environment.

Resources: https://github.com/phalcon/incubator/tree/master/Library/Phalcon/Acl/Adapter

#### Getting Ready...

This recipe uses the **Phalcon Developer Tools** which we will use to setup a project skeleton as well as Composer for installing the Phalcon Incubator resources.  We will need a database connection for this recipe.

#### How to do it...
Follow these steps to complete this recipe…

1) We need to have an application skeleton for experimentation.  If you already have such an application, you can skip this step. Create a project skeleton using the "modules" template:

`phalcon project access_list modules`

2) Install the Phalcon Incubator resources with:

`composer require phalcon/incubator`

3) Create the database `access_list`

```
CREATE TABLE `roles` (
  `name` VARCHAR(32) NOT NULL,
  `description` TEXT,
  PRIMARY KEY(`name`)
);

CREATE TABLE `access_list` (
  `roles_name` VARCHAR(32) NOT NULL,
  `resources_name` VARCHAR(32) NOT NULL,
  `access_name` VARCHAR(32) NOT NULL,
  `allowed` INT(3) NOT NULL,
  PRIMARY KEY(`roles_name`, `resources_name`, `access_name`)
);

CREATE TABLE `resources` (
  `name` VARCHAR(32) NOT NULL,
  `description` TEXT,
  PRIMARY KEY(`name`)
);

CREATE TABLE `resources_accesses` (
  `resources_name` VARCHAR(32) NOT NULL,
  `access_name` VARCHAR(32) NOT NULL,
  PRIMARY KEY(`resources_name`, `access_name`)
);

CREATE TABLE `roles_inherits` (
  `roles_name` VARCHAR(32) NOT NULL,
  `roles_inherit` VARCHAR(32) NOT NULL,
  PRIMARY KEY(roles_name, roles_inherit)
);
```

4) Add the ACL service to `app/config/services.php`:

```php
$di->setShared('acl', function() {
    $acl = new Phalcon\Acl\Adapter\Database([
        'db'                => $this->getDb(),
        'roles'             => 'roles',
        'rolesInherits'     => 'roles_inherits',
        'resources'         => 'resources',
        'resourcesAccesses' => 'resources_accesses',
        'accessList'        => 'access_list'
    ]);

    $acl->setDefaultAction(Phalcon\Acl::DENY);

    return $acl;
});
```

5) Add the security plugin to the dispatcher service by changing it to the following in `app/config/services_web.php`:

```php
$di->setShared('dispatcher', function() {
    $dispatcher = new Dispatcher();
    $dispatcher->setDefaultNamespace('AccessList\Modules\Frontend\Controllers');

    $eventsManager = $this->getEventsManager();

    $securityPlugin = new AccessList\SecurityPlugin();
    $securityPlugin->setDI($this);

    $eventsManager->attach('dispatch', $securityPlugin);

    $dispatcher->setEventsManager($eventsManager);

    return $dispatcher;
});
```

6) Create a security plugin `app/common/library/SecurityPlugin.php`:

```php
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
```

7) Create a CLI environment task for building the ACL with `app/modules/cli/tasks/BuildTask.php`:

```php
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
```

8) Create a test controller for the ACL with the `app/modules/frontend/controller/ProductsController.php`:

```php
<?php
namespace AccessList\Modules\Frontend\Controllers;

class ProductsController extends \Phalcon\Mvc\Controller
{
    public function indexAction()
    {
        return 'Accessed Products Index';
    }

    public function changeAction()
    {
        return 'Accessed Products Change';
    }

    public function addAction()
    {
        return 'Accessed Products Add';
    }

    public function cartAction()
    {
        return 'Accessed Products Cart';
    }
}
```

9) On the command line change to the project directory and create the ACL entries by executing the following command:

`./run build`

10) In the browser goto the following paths off of the base base of the project.  Lets see if we can guess each result before proceeding to the next How it works section.  Remember that the default routing scheme is setup so that if the controller or action are not specified that they each default to 'index'.

* /
* /index/index?role=blahblah
* /products
* /products?role=user
* /products/add?role=user
* /products/add?role=admin

#### How it works...

First off lets start by being very clear about a serious security related shortcut that we took in this recipe where for simplicity sake we use the HTTP query variable "role" to specify which ACL role that we will be using for a request.  In a normal production application this ACL setup would be accompanied by a fully featured authorization system and without that our ACL system can never be secure.  Now with this understanding we will allow ourselves this shortcut to be able to specifically look at just the ACL.

First lets look at our ACL service.  Now as is typical for all resources in Phalcon we are providing access to our ACL through the DI service.  This is useful because the ACL object depends upon the 'db' service and so by making this service as shared we can get the benefits of a singleton object while also handling the dependencies.

```php
$di->setShared('acl', function() {
    $acl = new Phalcon\Acl\Adapter\Database([
    'db'                => $this->getDb(),
    // ...
});    

Notice how we pass in each table to be used in our database ACL.  This allows us the flexibility to name our tables anything.

```php
'roles'             => 'roles',
'rolesInherits'     => 'roles_inherits',
'resources'         => 'resources',
'resourcesAccesses' => 'resources_accesses',
'accessList'        => 'access_list'
```

Notice how we are skipping the use of models for our ACL and we are going with direct table access as performed inside of the ACL.  Phalcon allows this kind of flexibility and performance.  Remember that full use of the ORM layer adds performance penalties and in our case we specifically want to go through the API that our ACL class provides.

Next we have perhaps the most important line of all.  This sets the default action of our ACL to deny permission unless there is a specific rule that grants the permission.

```php
$acl->setDefaultAction(Phalcon\Acl::DENY);
```

We will now look at `app/modules/cli/tasks/BuildTask.php` that we use to construct our ACL rules.  In our ACL there are three specific types of entries that we create; roles, resources and grants.

Roles are simply classifications of users.  Here we are creating a super user, a normal user and user who has yet to authenticate.

```php
$acl->addRole(new \Phalcon\Acl\Role('Admin'));
$acl->addRole(new \Phalcon\Acl\Role('User'));
$acl->addRole(new \Phalcon\Acl\Role('Anonymous'));
```

Next we will add our resources.  In this recipe we are using both the module and controller name together to create our resource name as this allows our application to work with multiple web modules with the second argument being the list of actions to support.

```php
$acl->addResource('frontend::index', ['index']);
$acl->addResource('frontend::products', ['index', 'change', 'add', 'cart']);
```

Finally we grant each role access to specific resources.

In the case of the 'Admin' user we are giving access to all resource-action pairs in the IndexControllr and ProductsController.

```php
$acl->allow('Admin', 'frontend::products', '*');
$acl->allow('Admin', 'frontend::index', '*');
```

For the "User" user we are granting some permissions but being careful not to give access to all the ability to "add" and "change" Product items.

```php
$acl->allow('User', 'frontend::products', ['index', 'cart']);
$acl->allow('User', 'frontend::index', '*');

Finally the "Anonymous" user only gains access to the index page.

```php
$acl->allow('Anonymous', 'frontend::index', '*');
```

Next lets look at the security plugin that we attached to our dispatcher.  Here we are implementing the event `beforeExecuteRoute` because this event is capable of canceling the dispatch process if our ACL conditions are not met.

```php
public function beforeExecuteRoute(\Phalcon\Events\Event $event, \Phalcon\Mvc\Dispatcher $dispatcher)
```

Here we are after four important bits of information; the role, controller, action and module name.  Remember that we defined the ACL resource names as the module name together with the controller name.  As explained earlier we are taking a shortcut here with the role by allowing the user to directly enter it from the HTTP query variable "role".  In a normal system the role name would be controlled by an authentication service and stored as a PHP session variable.

```php
$role = ($request->hasQuery('role')) ? $request->getQuery('role') : 'Anonymous';
$controllerName = $dispatcher->getControllerName();
$actionName = $dispatcher->getActionName();
$moduleName = $router->getModuleName();

$resourceName = "$moduleName::$controllerName";
```

Finally if the current role does not have access to to the resource then we will exit the request.  More advanced uses could instead use Dispatcher::forward method send the stop the execution of the current controller-action route onto another route such as permission denied action.


    if (!$acl->isAllowed($role, $resourceName, $actionName)) {
        echo "Not allowed!";
        exit;
    }
}
```
