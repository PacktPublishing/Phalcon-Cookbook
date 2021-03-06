
## Making unsuccessful requests fail softly

In any web application there will eventually be an error and it is important to be able to handle them in a graceful manner.  The Phalcon dispatch cycle is capable of intercepting PHP exceptions as well as missing routes.  On a production system it is important for security to disable the output of PHP exceptions on the page and this typically then just produces a white screen for the user.  While this is an improvement over displaying possibly sensitive exception information to the user it is not as professional as it could be.  In this recipe we will create a framework that is capable of gently handling these situations and displaying a clean page for the end user.

#### Getting Ready...

To use this recipe you will need to have a project skeleton with a configured Phalcon bootstraper, \Phalcon\Mvc\Router and at least one \Phalcon\Mvc\Controler.  In our example we will use a project scaffold generated by **Phalcon Developer Tools**.

For testing the recipe results you need to have a web server installed and configured for handling requests to your application. Your application must be able to take requests, and additionally, there must be such necessary components as Controllers, Views, and a bootstrap-file.

A database is not required for this recipe.

#### How to do it...
Follow these steps to complete this recipe…

1) First of all, we need to have an application which we will experiment with. If you already have such an application, you can skip this step. Create a simple application: `phalcon project request-errors simple`.

2) Now point the web browser at the root directory of the project. There should be a page with "Congratulations! ....".  If we see the *Volt directory can't be written* error message then permissions of the directory `app/cache` needs to be changed to allow the web server to write to it.

3)

Change loaders `app/config/loader.php`
```php
<?php
$loader = new \Phalcon\Loader();
$loader->registerDirs([
    $config->application->controllersDir,
    $config->application->modelsDir,
    $config->application->pluginsDir
])->register();
```

4)
Attach DispatcherException to the EventsManager service `app/config/services.php`
```php
<?php

$di->setShared('dispatcher', function() {
    $exceptionPlugin = new DispatcherException();
    $exceptionPlugin->setDI($this);

    $eventsManager = $this->getEventsManager();
    $eventsManager->attach('dispatch', $exceptionPlugin);

    $dispatcher = new Phalcon\Mvc\Dispatcher();
    $dispatcher->setEventsManager($eventsManager);

    return $dispatcher;
});
```

5)

Add file `app/plugins/DispatcherException.php`
```php
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
```

6)

Add two controllers:

`app/controllers/ErrorController.php`
```php
<?php

class ErrorController extends Phalcon\Mvc\Controller
{
    public function show404Action()
    {
    }

    public function unhandledExceptionAction()
    {
    }
}
```

`app/controllers/OopsController.php`
```php
<?php

class OopsController extends Phalcon\Mvc\Controller
{
    public function indexAction()
    {
        throw new \Exception('Error');
    }

}
```

7) Create views:

`app/views/error/show404.volt`

```
<div style="display: inline-block; padding: 30px; background-color: green;">
  There was a 404 Error
</div>
```

`app/views/error/unhandledException.volt`

```
<div style="display: inline-block; padding: 30px; background-color: yellow;">
  There was an error
</div>
```

8) In the web browser:

- Go to path `oops`.  You should see a yellow box with "There was an error".
- Go to path `ThisPage/DoesNotExist`.  You should a green box with "There was a 404 Error"

#### How it Works...

The Phalcon dispatch loop controls the execution of code by triggering a series of events in a specific order.  There are two main ways to plug into these events and the first is to attach a listener directly to the dispatcher itself and the other is to attach a plugin to the dispatcher so that it is notified of the events.  This later method has the advantage that plugins can be developed independently of each other to handle different aspects of the program.

The loader needs to be able to find the plugins directory so we add `$config->application->pluginsDir` to the list of directories that are searched when requesting a class that is not already available.

Then in `app/config/services.php` we define the "dispatcher" service.  Note that because this service is core to the system it will be automatically created by Phalcon if we have not defined it before it is needed the first time.

First we will create a new instance of the DispatcherException plugin and then set its dependency injector to $this since the service closure is bound to the DI.

$di->setShared('dispatcher', function() {
    $exceptionPlugin = new DispatcherException();
    $exceptionPlugin->setDI($this);

Then we will access the "EventsManager" service to get the object and then we will attach the DispatcherException object to it so that it will receive notifications of events related to "dispatch".

    $eventsManager = $this->getEventsManager();
    $eventsManager->attach('dispatch', $exceptionPlugin);

Finally we will create a new Dispatcher and then attach the EventsManager object to it so that it can notify.

    $dispatcher = new Phalcon\Mvc\Dispatcher();
    $dispatcher->setEventsManager($eventsManager);

Now that we have setup the dispatcher we will take a look at the DispatcherException plugin.  We have added a beforeException method to intercept the handling rather than letting it bubble all of the way up terminate the PHP script.  We have setup this plugin handle a variety of different types of exceptions.

Notice that we have named the method after the name of the event

    public function beforeException(Event $event, Dispatcher $dispatcher, $exception)
    {

First we will try to catch a few different types of exceptions to determine if it is caused by a route that could not be matched to a controller action.  The first case EXCEPTION_HANDLER_NOT_FOUND checks if the controller could not be found and the next case EXCEPTION_ACTION_NOT_FOUND checks if the action method could not be found.

Then if the route has failed then we redirect to ErrorController and execute the show404Action method.  Finally we return false to notify the dispatcher that the current action is canceled.  This has important implications in security related dispatcher plugins.

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

Finally we handle all other exceptions in a generic way by forwarding to unhandledException action on the ErrorController.

$dispatcher->forward([
    'controller' => 'error',
    'action'     => 'unhandledException',
]);

Finally we are arriving at the actions and since we created the views for those actions it will automatically display them.
