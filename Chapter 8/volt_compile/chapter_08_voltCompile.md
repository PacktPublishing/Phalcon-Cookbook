
## Precompiling Volt templates for increased performance and security

In this recipe we will create a multi-module application with both a Web and CLI environment.  We we first use the CLI environment to precompile the Volt templates for the Web environment while taking care to avoid difficult to solve edge cases and then we will use the Web environment for displaying our precompiled templates.  This approach is useful for increasing the performance of a website while also allowing us to set the permissions of our Volt cache directory to be unwritable by the web server.  This is useful because the compiled Volt templates are essentially phtml fragments and it is a good idea to never allow the web server to generate executable code.

#### Getting Ready...

This recipe uses the **Phalcon Developer Tools** which we will use to setup a project skeleton.  We will not need a database for this recipe.  This recipe will make advanced use of Phalcon modules and CLI and Web integration.

#### How to do it...
Follow these steps to complete this recipe…

1) We need to have a multi-module application skeleton for experimentation.  If you already have such an application, you can skip this step. Create a project skeleton using the "modules" template:

`phalcon project volt_compile modules`.

2) This project will be using the CLI interface to compile the Volt templates and so it is unnecessary to make the Volt cache directory writable by the web server.  In fact, it will prove our point better if we don't perform this step for this recipe.

3) Open `app/config/services.php` files and make the following changes to the `voltShared` service:

Add the following to the beginning the of `voltShared service function`:

```php
if (php_sapi_name() === "cli") {
    $compileAlways = true;
    $stat = true;
} else {
    $compileAlways = false;
    $stat = false;
}
```

Add the `compileAlways` and `stat` lines to the beginning of the $volt->setOptions call:

```php
$volt->setOptions([
    'compileAlways' => $compileAlways,
    'stat' => $stat,
    // ...
]);
```

4) Create our Volt compiler class at `app/common/library/ModuleCompiler.php`

```php
<?php
namespace VoltCompile;

class ModuleCompiler extends \Phalcon\DI\Injectable
{
    public function __construct()
    {
        if (php_sapi_name() !== "cli") {
            throw new \Exception('The module compiler must be run from the command line.');
        }
    }

    public function compile($moduleName)
    {
        $moduleClass = '\\' . __NAMESPACE__ . '\\Modules\\' . ucfirst($moduleName) . '\\Module';
        $module = new $moduleClass();

        // Create a temporary DI and register the module services to it.
        $diModule = new \Phalcon\DI();
        $module->registerServices($diModule);

        // Get an raw unresolved view function and bind it to our DI instead of our temporary DI.
        $viewFactory = \Closure::bind($diModule->getRaw('view'), $this->getDI());

        $this->compileVoltDir($viewFactory()->getViewsDir(), $viewFactory);
    }

    private function compileVoltDir($path, $viewFactory)
    {
        $dh = opendir($path);
        while (($fileName = readdir($dh)) !== false) {
            if ($fileName == '.' || $fileName == '..') {
                continue;
            }

            $pathNext = $path . $fileName;
            if (is_dir($pathNext)) {
                $this->compileVoltDir("$pathNext/", $viewFactory);
            } else {
                $this->getDI()
                    ->getVoltShared($viewFactory())
                    ->getCompiler()
                    ->compile($pathNext);
            }
        }

        // close the directory handle
        closedir($dh);
    }
}
```

Create the compiler task at `app/modules/cli/tasks/CompileTask`:

```php
<?php
namespace VoltCompile\Modules\Cli\Tasks;

class CompileTask extends \Phalcon\Cli\Task
{
    public function mainAction()
    {
        $this->incorrectAction();
    }

    public function incorrectAction()
    {
        $moduleCompiler = new \VoltCompile\ModuleCompiler();
        $moduleCompiler->setDI($this->getDI());

        $moduleCompiler->compile('frontend');

        echo 'Unfortunately there will be issues.';
    }

    public function correctAction()
    {
        $this->fillMissingServices();

        $moduleCompiler = new \VoltCompile\ModuleCompiler();
        $moduleCompiler->setDI($this->getDI());

        $moduleCompiler->compile('frontend');

        echo 'We did it the right way.';
    }

    private function fillMissingServices()
    {
        $config = $this->getDI()
            ->getConfig();

        $diPrimary = $this->getDI();

        $di = new \Phalcon\DI();
        require $config->application->appDir . 'config/services_web.php';

        foreach ($di->getServices() as $serviceName => $service) {

            // We will fill in any missing service that exists only for the web services
            // to ensure that Volt will understand DI services.
            if (!$diPrimary->has($serviceName)) {
                $diPrimary->set($serviceName, function() {});
            }
        }
    }
}
```

5) Add the following line somewhere in the Volt view `app/modules/frontend/views/index/index.php`:

```php
<p>Our session id is <b>{{ session.getId() }}</b></p>
```

6) On the command line change directories into the root path of this recipe.

Run the command: `./run compile`

We should see "Unfortunately there will be issues."

In go to the browser go to the base path of the recipe.  We should see a warning and then a fatal error.  If it is not visible on the page then it should be present in the web server error log.

7) Go Back to the command line and enter:

`./run compile correct`

We should see "We did it the right way."

In the browser go to the base path.  We should see that everything is fine and that we can see our browser session ID.

#### How it works...

We added some optional configuration settings to our `voltShared` service.  When `compileAlways` is true it requires that every Volt template is always compiled.  The `stat` option forces a check against the file system to see if the file exists.  We want to always do the most work in the CLI environment where we will be compiling the templates and in the web environment we want to assume that the Volt view already exist in the compiled form.

```php
if (php_sapi_name() === "cli") {
    $compileAlways = true;
    $stat = true;
} else {
    $compileAlways = false;
    $stat = false;
}
```

We will now look at the `ModuleCompiler` class to see how the compiling is actually preformed.

First we simply extend from the Phalcon\DI\Injectable class to typical add methods such as `setDI` and `getDI`.

```php
class ModuleCompiler extends \Phalcon\DI\Injectable
```

In the `compile` method we first create a variable that contains the module name and then we use that string variable to instantiate the module class.

```php
public function compile($moduleName)
{
    $moduleClass = '\\' . __NAMESPACE__ . '\\Modules\\' . ucfirst($moduleName) . '\\Module';
    $module = new $moduleClass();
```

Then we create a new empty DI object and we register all of the modules provided services into the empty DI.  There should not be any side effects in this operation as it should just be attaching service functions to the object.

```php
// Create a temporary DI and register the module services to it.
$diModule = new \Phalcon\DI();
$module->registerServices($diModule);
```

Next we get the raw "view" service from our temporary DI object and we bind it to our normal project DI object as the $viewFactory variable.  In this way we are able to use $viewFactory without modifying our normal DI.

```php
$viewFactory = \Closure::bind($diModule->getRaw('view'), $this->getDI());
```

Finally we call the recursive `compileVoltDir` method to recursve through the modules view directory to compile each view.

```php
    $this->compileVoltDir($viewFactory()->getViewsDir(), $viewFactory);
}
```

The `compileVoltDir` method is just a typical recursive function that operates on each file in the tree.

If the file is a directory then we recurse deeper into the directory.

```php
if (is_dir($pathNext)) {
    $this->compileVoltDir("$pathNext/", $viewFactory);
```

Otherwise we call out to the "voltShared" service by passing in a new Phalcon\Mvc\View object and then we get direct access to the volt compiler and we ask it to compile our current view template with the `compile` method.

```php
} else {
    $this->getDI()
        ->getVoltShared($viewFactory())
        ->getCompiler()
        ->compile($pathNext);
}
}
```

If we look back on the Volt service we see that it takes a single `$view` parameter which is used to setup where the template fragments will be compiled to based upon either the `compiledPath` setting.

```
$di->setShared('voltShared', function($view) {
    ...
    $volt = new VoltEngine($view, $this)
    $volt->setOptions([
        'compiledPath' => ...

```

Now lets look at the CompileTask to see how this is run.  The first thing that stands out is that there is a `incorrectAction` and `correctAction` method.  These two methods are identical except for one key difference that `correctAction` first calls `fillMissingServices()` to fill in some missing state.

To understand the difference in these two methods first look at `app/modules/frontend/views/index/index.volt` and notice the part `{{ session.getId() }}` that we added.  Now lets see how our two approaches compile it.

To observe the incorrect behaviour we simply call `./run compile` and then view the compiled Volt file: `cache/volt/modules%%frontend%%views%%index%%index.volt.php`.  Notice that it says `<?= $session->getId() ?>`.

Now for the correct behaviour we call `./run compile correct` and then notice that it is converted to `<?= $this->session->getId() ?>`.  So the two compilations are not equivalent.

The difference between the two is simply that the Volt compiler will convert a variable with a service name to `$this->serviceName` and if it cannot match it to a service then it will be converted as just `$serviceName`.  This difference arises since the CLI compiliation step doesn't load all of the same services that the web service does since many of them are not relevant to that environment.  So we remedy this by filling in all of the missing services in our CLI environment DI by using the `fillMissingServices` method.
