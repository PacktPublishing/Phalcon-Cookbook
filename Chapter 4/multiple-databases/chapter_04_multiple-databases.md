
## Storing models across multiple databases

In this recipe we will introduce a technique for seamlessly merging multiple databases together for use in our models.

#### Getting Ready...

This recipe is a bit different in that it defines and uses three separate databases.  We will also use **Phalcon Developer Tools** to setup a project skeleton.

#### How to do it...

1)

Create 3 databases:

`multiple-database`

```sql
CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `clients` (`id`, `name`) VALUES
(1, 'Bob'),
(2, 'Larry');

ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
```

`multiple-database_clients-1`

```sql
CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `products` (`id`, `name`) VALUES
(1, 'soap'),
(2, 'candles'),
(3, 'cups');

ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
```

`multiple-database_clients-2`

```sql
CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `products` (`id`, `name`) VALUES
(1, 'radio'),
(2, 'phone'),
(3, 'television');

ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
```

2)
* Change the configuration for the "db" service to connect to the "multiple-database" database.
* Add a new top level array to the config file for the client database service by adding the following:

```php
'databaseClient' => [
    'adapter'     => 'Mysql',
    'host'        => 'localhost',
    'username'    => 'root',
    'password'    => 'root',
    'charset'     => 'utf8',
],
```

**Notice:** We did not specify a "database" entry here as we normally would.  We will establish this later in the service definition for this connection.

3) Add the following to the services definition file at `app/config/services.php`

```php
$di->setShared('dbClient', function () use ($config) {
    $dbConfig = $config->databaseClient->toArray();
    $adapter = $dbConfig['adapter'];
    unset($dbConfig['adapter']);

    $client = Clients::getSelected();

    $dbConfig['dbname'] = 'multiple-database_clients-' . $client->id;

    $class = 'Phalcon\Db\Adapter\Pdo\\' . $adapter;

    return new $class($dbConfig);
});
```

4) Create two models:

`app/models/Clients.php`

```
<?php

class Clients extends Phalcon\Mvc\Model
{
    private static $selected;

    public static function getSelected()
    {
        if (!isset(self::$selected)) {
            throw new Exception('A client has not yet been selected.');
        }
        return self::$selected;
    }

    public function select()
    {
        if (isset(self::$selected)) {
            throw new Exception('The client may only be selected once.');
        }
        self::$selected = $this;
    }

    public function initialize()
    {
        $this->setConnectionService('db');
    }
}
```

`app/models/Products.php`

```php
<?php

class Products extends Phalcon\Mvc\Model
{
    public $id;

    public $name;

    public function initialize()
    {
        $this->setConnectionService('dbClient');
    }
}
```

5) Create a controller `app/controllers/ClientsController.php`

```php
<?php

class ClientsController extends Phalcon\Mvc\Controller
{
    public function indexAction()
    {
        $clientName = $this->request->getQuery('name', null, 'Bob');

        $client = Clients::findFirstByName($clientName);
        if (!$client) {
            return '<p>The client could not be found.</p>';
        }
        $client->select();

        $products = Products::find();

        $this->response->appendContent('<h3>Products</h3><ul>');
        foreach ($products as $product) {
            $this->response->appendContent('<li>' . $product->name . '</li>');
        }
        $this->response->appendContent('</ul>');

        $this->response->send();
    }
}
```

6) In the browser goto paths:
* `multiple-databases/clients` to see a list of products for the client 'Bob'.
* `multiple-databases/clients?name=Larry` to see products for client 'Larry'

#### How it works...

We created 3 databases for use in this project.  The `multiple-databases` database can be considered the core database for the project and the other two are specifically each tied to Client record.  Lets look at the "dbClient" database service definition:

```php
$di->setShared('dbClient', function () use ($config) {
```

Here we see that we have named the database service a non-standard name "dbClient".  Later we will need to let our Products model know that it needs to use a different database service than the default "db" service.

Skipping ahead a few lines we see that we are calling a static method on the Clients model to get a "selected" client.  This "getSelected" static function is something that we added to our Clients model and we'll look at it a bit later.  For now all that we need to know is that there is a specific client record that we are using.

```php
    $client = Clients::getSelected();
```

Then next we are using the 'multiple-database_clients-' prefix for client databases and then applying the primary key from the client record to the database name so that each client will have their own database for storing records.  Possibly these databases could reside on different servers even and this would not need to be hard coded into the model.

```php
    $dbConfig['dbname'] = 'multiple-database_clients-' . $client->id;
```

Now we'll look at the Clients model:

First notice that we have defined a private static property $selected as well as a static getter to retrieve that property.

```php
<?php
class Clients extends Phalcon\Mvc\Model
{
    private static $selected;

    public static function getSelected()
    {
        if (!isset(self::$selected)) {
            throw new Exception('A client has not yet been selected.');
        }
        return self::$selected;
    }
```

Next we implement a normal public function to be called on a specific record that saves itself to the private static property.  We will call this later in the controller once we had found a specific client record.

```php
public function select()
{
    if (isset(self::$selected)) {
        throw new Exception('The client may only be selected once.');
    }
    self::$selected = $this;
}
```

Finally in the initialize event we are specifying that this model should use the default "db" connection service.  In this case we are doing this only to bring to light the concept of a swappable connection service and since it is the same as the default it doesn't change anything.

```php
    public function initialize()
    {
        $this->setConnectionService('db');
    }
}
```

To conclude the models we will quickly look at Products model.  The only interesting thing happening here is that we are specifying the connection service to be the non-standard `dbClient`.  This is the key part that allows our Products models to seamlessly reside in another database and possibly on another server.  In fact this connection could be to an entirely different type of database server such as Postgres, Oracle, MongoDB or SQLite.

```php
public function initialize()
{
    $this->setConnectionService('dbClient');
}
```

Now we'll look at the ClientsController code to demonstrate how to tie all of this functionality together.

```php
    public function indexAction()
    {
        $clientName = $this->request->getQuery('name', null, 'Bob');
```

Here we are taking a client name from the query variables and filling in the default "Bob" if known is provided.  This allows us to put `?name=Larry` in the URL to specify a different search.

Next we do a very typical search for a single record using the magic method findFirstByFIELD that is provided for us by Phalcon\Mvc\Model.  We then handle a search miss in clean way by returning a string that will be included in the `content()` portion of our view layout.  Notice that a query miss here is not exceptional behavior and we handle it normally.

```php
        $client = Clients::findFirstByName($clientName);
        if (!$client) {
            return '<p>The client could not be found.</p>';
        }
```

Here is the good part where we select the client record that is then later used to specify which database we will use in the `dbClient` connection service.

```php
        $client->select();
```

Finally we search for all products and then we do the routine output formatting and iterating upon the Products.

```php
        $products = Products::find();

        $this->response->appendContent('<h3>Products</h3><ul>');
        foreach ($products as $product) {
            $this->response->appendContent('<li>' . $product->name . '</li>');
        }
        $this->response->appendContent('</ul>');

        $this->response->send();
    }

}
```

Notice here that we are now able to deal with Products models seamlessly now that they reside in a different database.
