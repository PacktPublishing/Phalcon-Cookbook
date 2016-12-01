
## Sending server log messages to the browser console

[This recipe may need to be slightly revised around Firefox 50 due to the change in the browser design.  Firebug 3 will be integrated directly into the built-in Firefox Developer Console and it may very well be that Firebug 3 is the only compatible version.  For this reason I am not spending time on screenshot images at this moment.  As of the time of this writing Firefox 47 is the latest stable release and I expect the situation to become much more clear over the Firefox 48-49 time period.  Soon Firefox 50 will enter into a later developer stream so that I will be able to try it out before release.  This might just end up being a case of bad timing for our initial book release since these changes to Firefox are the largest to date since its release.  Its possible that we will need to do some hot off of the presses changes to the book before release.  A little time will sort it all out.]

[Now for the real intro]

Debugging web applications is difficult and large part of that difficulty is needing to switch between so many applications such as; the terminal for viewing the web server error log, the text editor, the browser and file explorer.  Some of this pain can be remedied by using the Firelogger stack to view messages right in the browser console as this can bring the number of required applications to just three.

#### Getting Ready...

This recipe uses **Phalcon Developer Tools** to setup a project skeleton and Composer to install PHP libraries from the Phalcon Incubator project.  A database is not required.

We need to install Composer for installing the Phalcon Incubator libraries.  Get the open source Composer tool at: https://getcomposer.org/

In this recipe we need Firefox with the Firebug and Firelogger extensions installed.  While using Firefox open up the following links and click "Add to Firefox" install and then restart the browser as needed.

* Firebug - https://addons.mozilla.org/en-US/firefox/addon/firebug/
* Firelogger - https://addons.mozilla.org/en-US/firefox/addon/firelogger/

With the extensions installed to any webpage and follow the following directions.

Now we will need to enable some panes in the Firebug console. Open tabs; `Console`, `Net` and `Logger` tab and click the "enable" button in each pane to setup the requirements for using Firelogger.

#### How to do it...
Follow these steps to complete this recipe…

1) We need to have an application skeleton for experimentation.  If you already have such an application, you can skip this step. Create a project skeleton using the "simple" template:

`phalcon project browser-logging simple`.

2) Now point the web browser at the root directory of the project. There should be a page with "Congratulations! ....".  If we see the *Volt directory can't be written* error message then permissions of the directory `app/cache` needs to be changed to allow the web server to write to it.

3) We will need to go to the command line to install the Phalcon Incubator libraries.  In the terminal go to the directory for this recipe and enter:

```php
composer require phalcon/incubator:2.1.x-dev --ignore-platform-reqs
```

This will build the composer.json file and install the dependencies into the `vendor` directory.

4) We need to add the Composer autoloader file to the bootstrap file.  So open up `app/config/services.php` and add the following somewhere in the `loader` service.  Make sure to define the loader service as shared so that it can only include the Composer autoloader a single time:

```php
/**
 * Read Composer auto-loader
 */
include __DIR__ . '/../../vendor/autoload.php';
```

5) Create the `logger` service in your `services.php` file.

Note: the class `Phalcon\Logger\Adapter\Firelogger` is being brought in from the phalcon/incubator package via the autoloader that we setup.

```php
/**
 * Logger Service
 */
$di->setShared('logger', function () {
    return new Phalcon\Logger\Adapter\Firelogger('debug');
});
```

6) Create `app/controllers/LogController.php` to use the logger:

```php
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
```

7) In the browser goto path `log` off of the base path of the project.  If the instructions in the Getting Started section for setting up Firelogger were followed then we should be able to see the log messages in the "Logger" pane of Firebug.  If the messages don't appear then try refreshing the browser page while Firebug is open.

#### How it works...

There are two parts to the Firelogger solution; the backend that sends the log messages and the frontend that interprets  them.  There are many solutions for sending these specially formatted messages to the browser but in our case the `Phalcon\Logger\Adapter\Firelogger` class from the phalcon/incubator package will be doing this for us.  These messages are sent to the browser through the HTTP response headers.  Once they arrive to the browser the Firelogger extension can be used to display these normally invisible header entries.  Firelogger simply leverages the Firebug debugging framework rather than reinventing the wheel.

[Revise this around Firefox ~50 after Firebug has become compatible with the post-Electrolisys Firefox.  The user interface might change.]
