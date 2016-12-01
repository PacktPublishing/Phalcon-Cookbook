
## Using the query builder for complex yet fluent model querying

Phalcon has a powerful query builder that can generate powerful queries without writing out long a possibly difficult to understand PHQL statements.  This is accomplished by creating a Phalcon\Mvc\Model\Query\Builder object and then chaining a series of methods together to generate a Phalcon\Mvc\Model\Query.  This technique has an advantage of having IDE support (....).

#### Getting Ready...

This recipe uses the `chapter_04` database that we setup in the chapter introduction and **Phalcon Developer Tools** which we will use to setup a project skeleton.

#### How to do it...
Follow these steps to complete this recipe…

1) We need to have an application skeleton for experimentation.  If you already have such an application, you can skip this step. Create a project skeleton using the "simple" template:
`phalcon project query-builder simple`.

2) Now point the web browser at the root directory of the project. There should be a page with "Congratulations! ....".  If we see the *Volt directory can't be written* error message then permissions of the directory `app/cache` needs to be changed to allow the web server to write to it.

3) Ensure that your configuration file is setup with the correct host, database, username and password.  This recipe will use the `chapter_04` database that we established in the Chapter 4 introduction.

4) Create three models:

`Hats.php`

```php
class Hats extends Phalcon\Mvc\Model
{
    public function initialize()
    {
        $this->hasManyToMany(
            "id",
            "HatsColors",
            "hats_id",
            "colors_id",
            "Colors",
            "id",
            [
                'alias' => 'colors'
            ]
        );
    }
}
```

`Colors.php`

```php
<?php

class Colors extends Phalcon\Mvc\Model
{
    public function initialize()
    {
        $this->hasManyToMany(
            "id",
            "HatsColors",
            "colors_id",
            "hats_id",
            "Hats",
            "id",
            [
                'alias' => 'hats'
            ]
        );
    }
}
```

`HatsColor.php`

```php
<?php

class HatsColors extends Phalcon\Mvc\Model
{
}
```

5) Create controller `BuilderController`

```php
<?php

class BuilderController extends Phalcon\Mvc\Controller
{
    public function example1Action()
    {
        $builder = $this->modelsManager
            ->createBuilder();

        $builder = $builder->from('Hats');
        $builder = $builder->where('Hats.name = "panama"');
        $builder = $builder->orWhere('Hats.name = "baseball"');

        $query = $builder->getQuery();
        $generatedSql = $query->getSql()['sql'];
        $hats = $query->execute();

        $this->response->appendContent($generatedSql . '<br><br>');
        foreach ($hats as $hat) {
            $this->response->appendContent($hat->name . '<br>');
        }
        $this->response->send();
    }

    public function example2Action()
    {
        $hats = $this->modelsManager
            ->createBuilder()
            ->from('Hats')
            ->join('Colors')
            ->distinct(true)
            ->inWhere('Colors.name', ['black', 'red'])
            ->getQuery()
            ->execute();

        foreach ($hats as $hat) {
            $this->response->appendContent($hat->name . '<br>');
        }
        $this->response->send();
    }
}
```

6) Point the browser at `/builder/example1` of the root path of the project.  There should be an SQL statement at the top of the page followed the the printout of hats returned from the query.

7) Point the browser at `/builder/example2` to see the print out of the hats returned from the query.

#### How it works...

Lets take a look at `example1Action` in the BuilderController.

Here we are calling to the DI service called `modelsManager` and asking it to create a Phalcon\Mvc\Model\Query\Builder object for us.

```php
public function example1Action()
{
    $builder = $this->modelsManager
        ->createBuilder();
```

He we are calling a series of methods on the `$builder` object to add additional conditions and requirements to it.  Notice that after each call saving a new reference to the `$builder` object.

```php
$builder = $builder->from('Hats');
$builder = $builder->where('Hats.name = "panama"');
$builder = $builder->orWhere('Hats.name = "baseball"');
```

Although this is perfectly valid in the next example we will demonstrate a cleaner way to chain the entirety of the query building process into one concise sequence.  For now this approach allows us to talk about the class types of the return objects as we build the query.

Now that we have the basic requirements for a SQL statement we can generate a Phalcon\Mvc\Model\Query object.

```php
$query = $builder->getQuery();
```

Now we will store the actual SQL generated from our query.  This feature is very useful when debugging a troublesome program but for now we are only doing it now for learning purposes.

```
$generatedSql = $query->getSql()['sql'];
```

Finally we will retrieve our hat models.

```
$hats = $query->execute();
```

Now we have all of the data that we need so we will printout the generated SQL from our query and also print out all of the Hats that were retrieved.

```php
$this->response->appendContent($generatedSql . '<br><br>');
foreach ($hats as $hat) {
    $this->response->appendContent($hat->name . '<br>');
}
$this->response->send();
```

Now lets look at our second example in `example2Action`.

As promised we are now chaining the entire query process into one long execution without storing the intermediate objects.

```php
$hats = $this->modelsManager
    ->createBuilder()
    ->from('Hats')
    ->join('Colors')
    ->distinct(true)
    ->inWhere('Colors.name', ['black', 'red'])
    ->getQuery()
    ->execute();
```

We are starting with the Hats model with `->from('Hats')` and then we are joining it against the `Colors` model with `->join('Colors')`.  Due to our join condition that we setup in our Hats model to our Colors model going through HatsColors we are able to automatically fill in the join conditions between three tables with just these two lines.  Powerful stuff!

We need to declare that we do not want duplicate models returned with `->distinct(true)` as this is the standard SQL behavior.

Now we are ready to add a condition to the WHERE clause of the query by adding the call `->inWhere('Colors.name', ['black', 'red'])`.  Notice that we are passing in an array of two values with the second argument.  This creates placeholders in the SQL and then fills in their bind values during execution.  This is more secure and additionally we are able to bind an entire array automatically and this is something that native PDO database access does not support.

At this point we still have a builder object and by calling `getQuery` we retrieve the Phalcon\Mvc\Model\Query object which we then execute to receive the Phalcon\Mvc\Model\Resultset\Simple that contains all of our models and can be iterated upon.

There are a few other features supported by the query builder and they can be found here at: https://docs.phalconphp.com/en/latest/reference/phql.html#creating-queries-using-the-query-builder
