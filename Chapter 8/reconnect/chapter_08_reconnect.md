
## Handling a database timeout with a reconnect plugin

Long running server processes that are accessing the database will run into connection timeout errors if the period of inactivity on the connection is longer than the setting on the database.  Although this is typically only an issue with a command line script, in this script we will use a normal MVC web application.  We will first demonstrate the issue by allowing the database connection to timeout due to inactivity and then next we will will create a solution to this.

#### Getting Ready...

This recipe uses the **Phalcon Developer Tools** which we will use to setup a project skeleton.  We will need a database to test our reconnection plugin.

#### How to do it...
Follow these steps to complete this recipe…

1) We need to have an application skeleton for experimentation.  If you already have such an application, you can skip this step. Create a project skeleton using the "simple" template:

`phalcon project reconnect simple`.

2) Now point the web browser at the root directory of the project. There should be a page with "Congratulations! ....".  If we see the *Volt directory can't be written* error message then permissions of the directory `app/cache` needs to be changed to allow the web server to write to it.

3) Open up the `app/config/loader.php` file and register the plugins directory by adding `$config->application->pluginsDir` to it.

4) Create database `reconnect`:

```sql
CREATE TABLE `records` (
  `id` int(11) NOT NULL,
  `name` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


INSERT INTO `records` (`id`, `name`) VALUES
(1, 'stuff'),
(2, 'things'),
(3, 'John'),
(4, 'cars'),
(5, 'anything');

ALTER TABLE `records`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
```

5) We will need to edit the Mysql/MariaDB configuration file by adding `wait_timeout = 5` to it and then restarting the database server.  If the `wait_timeout` option already exists then comment that line out and add in our new value.

6) Create the model `app/models/Records.php`:

```
<?php

class Records extends Phalcon\Mvc\Model
{
}
```

7) Add the following to `db` services definition right after the connection object is created:

```php
if (defined('RECONNECT_AFTER_TIMEOUT')) {
    $eventsManager = new Phalcon\Events\Manager();

    $reconnectPlugin = new ReconnectPlugin();
    $reconnectPlugin->initializeConnection($connection);
    $eventsManager->attach('db', $reconnectPlugin);

    //Assign the eventsManager to the db adapter instance
    $connection->setEventsManager($eventsManager);
}

8) Create the the reconnect plugin `app/library/ReconnectPlugin`:

```php
<?php

class ReconnectPlugin extends Phalcon\Mvc\User\Plugin
{

    public function initializeConnection($connection)
    {
        // Make sure that the query cache is off.
        $connection->query('SET QUERY_CACHE_TYPE = OFF;');

        // Ask the database how many seconds the timeout is set at
        $result = $connection
            ->query("SHOW VARIABLES LIKE 'wait_timeout'")
            ->fetchArray();

        $timeout = (int) $result['Value'];
        if ($timeout > 5) {
            error_log('Hey! We need to set the timeout to five seconds so that we can test the timeout reconnect.');
        }
        $connection->timeout = $timeout;

        // Set the connection start time and the timeout duration
        $connection->start = time();
    }

    public function beforeQuery(Phalcon\Events\Event $event, $connection)
    {
        // We want to return if we are running our test calculation query.
        if ($connection->getSQLStatement() == 'SELECT 1+2+3') {
            return;
        }

        // If the connection duration is past the timeout amount then we will reconnect.
        $activeTimeout = time() - $connection->start;
        if ($activeTimeout > $connection->timeout) {
            error_log("First attempt to reconnect.");
            $connection->connect();
            $connection->start = time();
        }

        try {
            // We'll issue a simply query that doesn't require any data.
            $result = $connection
                ->query('SELECT 1+2+3')
                ->fetch();

            // If the result is not correct then we will try to reconnect again.
            if ($result[0] != 6) {
                error_log("Second attempt to reconnect.");
                $connection->connect();
            }
        } catch (\PDOException $e) {

            // If this was unsuccesful then we will try one last time to reconnect.
            error_log("Third attempt to reconnect.");
            $connection->connect();
        }
    }
}
```

9) Create the controller `app/controllers/TimeoutController.php`:

```
<?php

class TimeoutController extends Phalcon\Mvc\Controller
{
    public function indexAction()
    {
        $this->test();

        $this->response->send();
    }


    public function reconnectAction()
    {
        define('RECONNECT_AFTER_TIMEOUT', true);

        $this->test();

        $this->response->send();
    }

    protected function test()
    {
        try {
            Records::count();

            sleep(6);

            Records::count();
        } catch (\Exception $e) {
            $this->response->appendContent('<br><p>The reconnect failed.<br></p>');
            $this->response->appendContent('<p>' . $e->getMessage() . '</p>');
            return;
        }

        $this->response->appendContent('The timeout reconnection succeeded.');
    }

}
```

10) In the browser go to the following pages:

*  `/reconnect/timeout`:  We will need to wait 6 seconds and then we should see some error and warning messages along with `The reconnect failed.`
* `/reconnect/timeout/reconnect`: We will need to wait 6 seconds and then we should see a success message `The timeout reconnection succeeded.`

#### How it works...

In the Mysql configuration there is a setting called `wait_timeout` that determines the amount of seconds that database server will hold onto an active connection before breaking it.  Typically this setting is set at a value between 300-600 seconds.  Our first inclination might be to simply set this value to a much higher value but then we are not actually solving the eventual timeout on a long running CLI script.  In this recipe we set the timeout to a very low value of 5 seconds so that we can test the timeout without needing to wait an absurdly large amount of time.

In our `db` service we attach our reconnect plugin to the database connection.

We will check to see if the RECONNECT_AFTER_TIMEOUT constant is set before adding our plugin.  It is not recommended to use this approach in production code as we are only doing it here to allow us to test the system with and without the plugin.

```php
if (defined('RECONNECT_AFTER_TIMEOUT')) {
    ...
}
```

Now we perform the typical plugin setup by creating an event manager (or using the default DI one), creating the plugin object, attaching it to the events manager and then finally calling the `setEventsManager` method on the object that we wish to listen to events on.

```php
    $eventsManager = new Phalcon\Events\Manager();

    $reconnectPlugin = new ReconnectPlugin();
    $reconnectPlugin->initializeConnection($connection);
    $eventsManager->attach('db', $reconnectPlugin);

    //Assign the eventsManager to the db adapter instance
    $connection->setEventsManager($eventsManager);
}
```

However there is one non-typical line here that we must call to allow the reconnect plugin to setup the database connection.

```php
$reconnectPlugin->initializeConnection($connection);
```

Now for the moment we'll ignore the ReconnectPlugin implementation details to see how this plugin will effect our controller code in the TimeoutController class.

The first indexAction is designed to show how the system will fail without the ReconnectPlugin.

```php
public function indexAction()
{
    $this->test();

    $this->response->send();
}
```

When we look inside of the `test` method we see a simple way to test the timeout.  Since we set the MySQL server timeout to only five seconds then we can trigger the timeout by simply waiting six seconds between our database queries.  This bit of code below will trigger warnings and errors.

```php
Records::count();

sleep(6);

Records::count();
```

Finally in the `reconnectAction()` method we set the RECONNECT_AFTER_TIMEOUT constant so that ReconnectPlugin is added to the connection before we call our database queries.  This should succeed by reconnecting after the timeout.

```
define('RECONNECT_AFTER_TIMEOUT', true);

$this->test();
```

Finally we will look at the ReconnectPlugin itself.  We'll first need to disable the query cache just in case it is on.

```php
public function initializeConnection($connection)
{
    // Make sure that the query cache is off.
    $connection->query('SET QUERY_CACHE_TYPE = OFF;');
```

Next we will retrieve the wait_timeout value from the database itself rather than assuming the value.  We are waiting 6 seconds in our controller code and so we need the real timeout value to be less than this amount.

    // Ask the database how many seconds the timeout is set at
    $result = $connection
        ->query("SHOW VARIABLES LIKE 'wait_timeout'")
        ->fetchArray();

    $timeout = (int) $result['Value'];
    if ($timeout > 5) {
        error_log('Hey! We need to set the timeout to five seconds so that we can test the timeout reconnect.');
    }
    $connection->timeout = $timeout;
```

Finally we record the starting time by saving it to the connection object itself.

```php`
    // Set the connection start time and the timeout duration
    $connection->start = time();
}
```

In the `beforeQuery` event we first check to see if we can ignore our test query by checking to see if it is 'SELECT 1+2+3'.

```php
public function beforeQuery(Phalcon\Events\Event $event, $connection)
{
    // We want to return if we are running our test calculation query.
    if ($connection->getSQLStatement() == 'SELECT 1+2+3') {
        return;
    }
```

Next we attempt to reconnect if the known timeout duration has been succeeded.

```php
    // If the connection duration is past the timeout amount then we will reconnect.
    $activeTimeout = time() - $connection->start;
    if ($activeTimeout > $connection->timeout) {
        error_log("First attempt to reconnect.");
        $connection->connect();
        $connection->start = time();
    }
```

Next we will perform a simple query to add 1 + 2 + 3 to see if the database connection is still active.  Since this query is working only with constants then the database server can process it without accessing any table data.  If the result does not equal 6 then we will reconnect again to be sure.

```php
    try {
        // We'll issue a simply query that doesn't require any data.
        $result = $connection
            ->query('SELECT 1+2+3')
            ->fetch();

        // If the result is not correct then we will try to reconnect again.
        if ($result[0] != 6) {
            error_log("Second attempt to reconnect.");
            $connection->connect();
        }
    } catch (\PDOException $e) {

        // If this was unsuccesful then we will try one last time to reconnect.
        error_log("Third attempt to reconnect.");
        $connection->connect();
    }
}
```

We sure to investigate the web server error log file to see when the connection is timing out.  Also be sure to restore the timeout setting to something much more normal.
