
## Improved exception reports with Phalcon Debug component

Phalcon includes a powerful debugging component that can make debugging a much more casual experience.  By instantiating this component early in our application bootstrap we can receive a visual layout of the backtrace and other important state.  The backtrace is formatted to include the colored source code of every file that was loaded and it will even highlight the specific line that the error occurred on.  Additionally for very core Phalcon class that was loaded during execution will be presented with a link to the official Phalcon documentation.

#### Getting Ready...

This recipe uses the **Phalcon Developer Tools** which we will use to setup a project skeleton and database access is not required.

#### How to do it...
Follow these steps to complete this recipe…

1) We need to have an application skeleton for experimentation.  If you already have such an application, you can skip this step. Create a project skeleton using the "simple" template:

`phalcon project improved_exception simple`.

2) Now point the web browser at the root directory of the project. There should be a page with "Congratulations! ....".  If we see the *Volt directory can't be written* error message then permissions of the directory `app/cache` needs to be changed to allow the web server to write to it.

3) First we will need to load the debug component and to ensure that all exceptions are left uncaught by a try/catch block.  If the Developer Tools were used to create the project then open up the index.php file and simply comment out the try/catch code while leaving the remainder as it is.  Now somewhere before the Application object is instantiated add the following to enable the debug component:

```php
(new \Phalcon\Debug())->listen();
```

4) Add a controller `app/controllers/FailController.php`:

```php
<?php

class FailController extends \Phalcon\Mvc\Controller
{
    public function indexAction()
    {
        $itemBuilder = $this->modelsManager
            ->createBuilder(1, 2, 3);
    }
}
```

5) In the browser go to the path `fail` from the root path of the project.  You should see the Phalcon Debug component output.

#### How it works...

The Phalcon Debug component registers itself to handle all uncaught exceptions in execution.  For this reason we need to make sure that the exceptions are not handled in the program and so fall out at the end of the script.  The component is able to step through the execution backtrace to construct the HTML representation of this data.  If we look at the source code of the page we will see that the CSS and Javascript used to present this output is coming from static.phalconphp.com.

[Write about how to use local resources once https://github.com/phalcon/cphalcon/issues/11943 is implemented.]

If we look at the tabs below the exception message we will should see; Backtrace, Request, Server, Included Files and Memory.

First we'll look at the Backtrace.  In our example we should see the stacktrace go from #0 through #5.  If we look at #0 we will see that the component was able to identify that the error took place within an object of type Phalcon\Mvc\Model\Manager and so it is able to create a link to the Phalcon documentation there.  Then #1 through #4 we can see the stac trace of each function that was called leading up to this point and finally with step #5 we are shown the colored source on the index.php file.  The debug component will show the source of every PHP file along the stack trace.

Next if we look at Request and Server we can see the contents of the super globals $_REQUEST and $_SERVER.  The Included Files tab simply lists out all file that were required or included during the program execution.  Finally the Memory tab displays the memory used.
