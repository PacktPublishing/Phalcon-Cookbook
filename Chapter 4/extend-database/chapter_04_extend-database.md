
## Getting the most out of your RDBMS of choice

The Phalcon ORM (Object Relation Model) is quite powerful and it allows the same (PHQL) search language to be used across multiple databases including Mysql, PostgresSQL, Oracle and SQLite.  Underlying all of the built-in database adapters is support for common SQL functions that are in common between all of the supported systems or at least can be easily mapped to each one.

However sometimes it may be necessary to use a feature of a particular database system that is not supported by the built in database adapters.  In this recipe we will be extending the Mysql adapter with a "dialect" that allows it to use the Fulltext indexing capability of that system.

#### Getting Ready...

This recipe uses the `chapter_04` database that we setup in the chapter introduction and **Phalcon Developer Tools** which we will use to setup a project skeleton.

#### How to do it...
Follow these steps to complete this recipe…

1) We need to have an application skeleton for experimentation.  If you already have such an application, you can skip this step. Create a project skeleton using the "simple" template:
`phalcon project extend-database simple`.

2) Now point the web browser at the root directory of the project. There should be a page with "Congratulations! ....".  If we see the *Volt directory can't be written* error message then permissions of the directory `app/cache` needs to be changed to allow the web server to write to it.

3) Ensure that your configuration file is setup with the correct host, database, username and password.  This recipe will use the `chapter_04` database that we established in the Chapter 4 introduction.

4)

Configure loader to use library path for class lookup in file `app/config/loader.php`:

```php
$loader->registerDirs(
    [
        $config->application->controllersDir,
        $config->application->modelsDir,
        $config->application->libraryDir
    ]
)->register();
```

5) Add the following line to your database configuration.
```
'dialectClass' => 'MysqlExtended'
```
In our project scaffold this is located in `app/config/config.php`.

6) Add database dialect class at `app/library/MysqlExtended.php`

```php
<?php

class MysqlExtended extends Phalcon\Db\Dialect\Mysql
{
    public function getSqlExpression(array $expression, $escapeChar = null, $bindCounts = null)
    {
        if ($expression["type"] == 'functionCall') {
            switch ($expression["name"]) {
                case 'FULLTEXT_MATCH':
                    if (count($expression["arguments"]) < 2) {
                        throw new \Exception('FULLTEXT_MATCH requires 2 parameters');
                    }
                    $arguments = array();
                    $length = count($expression["arguments"]) - 1;
                    for ($i = 0; $i < $length; $i++) {
                        $arguments[] = $this->getSqlExpression($expression["arguments"][$i]);
                    }
                    return 'MATCH(' . join(', ', $arguments) . ') AGAINST (' .
                    $this->getSqlExpression($expression["arguments"][$length]) . ')';
                case 'FULLTEXT_MATCH_BMODE':
                    if (count($expression["arguments"]) < 2) {
                        throw new \Exception('FULLTEXT_MATCH requires 2 parameters');
                    }
                    $arguments = array();
                    $length = count($expression["arguments"]) - 1;
                    for ($i = 0; $i < $length; $i++) {
                        $arguments[] = $this->getSqlExpression($expression["arguments"][$i]);
                    }
                    return 'MATCH(' . join(', ', $arguments) . ') AGAINST (' .
                    $this->getSqlExpression($expression["arguments"][$length]) . ' IN BOOLEAN MODE)';
            }
        }
        return parent::getSqlExpression($expression, $escapeChar);
    }
}
```

7) Create Controller `app/controllers/QueryController.php`

```php
<?php

class QueryController extends Phalcon\Mvc\Controller
{
    public function indexAction()
    {
        $sql = "SELECT * FROM Posts WHERE FULLTEXT_MATCH_BMODE(text, '+technology')";
        $query = new Phalcon\Mvc\Model\Query($sql, $this->getDI());
        $posts = $query->execute();

        foreach ($posts as $post) {
            $this->response->appendContent("{$post->id}: {$post->text}<br>");
        }
        $this->response->send();
    }
}
```

8) In the browser go to path `query` in the root path of the project.  You should see `1: Technology is a very powerful force that seeks to change society for the better.`

#### How it works...

The built in Phalcon Mysql database adapter only supports a subset of the possible functions available on the database server and our class MysqlExtended adds some new functions for Mysql specific behavior.  This approach allows us to know specifically when we implementing features that will not be available on other supported databases like for example PostresSQL.

The configuration of our database adapter is almost entirely the same as normal except that we are adding a single line to the configuration that specifies that we also use our dialect class to provide extra functionality.  This is accomplished with the following line in our database service:

```
`'dialectClass' => 'MysqlExtended'
```

Now lets look in our dialect class `MysqlExtended`.  Notice that this class only implements a single public method `getSqlExpression`.  The body of this method is made up of a switch statement that we'll get into shortly but first notice the very end of the function where if there were no matches we simply return the default behaviour.
```php
return parent::getSqlExpression($expression, $escapeChar);
```

Inside of the switch statement we are implementing two new functions; `FULLTEXT_MATCH` and `FULLTEXT_MATCH_BMODE`.  Notice that for each new defined function we are constructing and returning a new expression from values in the `$expression` variable.  How is works is that Phalcon is parsing the PHQL and is able to identify a function call and to extract the arguments and it is up to us to generate the actual SQL condition fragment that will be returned.  So really we could do anything here as long as we generate an SQL fragment that our chosen database can understand.

So in the `FULLTEXT_MATCH` definition we see that we are returning the SQL fragment:
```php
return 'MATCH(' . join(', ', $arguments) . ') AGAINST (' .
$this->getSqlExpression($expression["arguments"][$length]) . ')';
```

So again to review: Phalcon parses the PHQL and we can intercept particular function calls with our database dialect class to return new valid SQL condition fragments or otherwise Phalcon will attempt to use default behavior if it is defined.

Moving on to our QueryController we are executing a query with PHQL that uses our new defined function `FULLTEXT_MATCH_BMODE`.

First we'll look at the PHQL.  Here we are calling one of our new functions.

```php
$sql = "SELECT * FROM Posts WHERE FULLTEXT_MATCH_BMODE(text, '+technology')";
```

Next we define the query and execute it.  Notice that we pass in the dependency injector as the second argument.  This is a good example of the power and ease of using the Phalcon DI system as all that is needed to fully hook the Query class up to any service that is defined in our system.

```php
$query = new Phalcon\Mvc\Model\Query($sql, $this->getDI());
$posts = $query->execute();
```

Next we iterate on the `$posts` Phalcon\Mvc\Model\Resultset\Simple object to add each record to the output buffer and then we send the response to the browser.
```php
foreach ($posts as $post) {
    $this->response->appendContent("{$post->id}: {$post->text}<br>");
}
$this->response->send();
```
