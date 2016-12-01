
## Fetching models from raw SQL queries

In this recipe we will learn how to retrieve data using raw SQL when PHQL doesn't meet our needs.  Although PHQL does a lot and can be extended by using custom database specific dialects it isn't always enough.  It would be terrible if we were simply out of luck and unable to fulfill a niche need and fortunately Phalcon is able to get out of the way and to provide us raw SQL access to our database service.  This means that we can use PHQL based technology for almost everything in our system and then if we hit a wall then we can switch over to writing the query directly with SQL.

**Note:** One potential downside to consider when using raw SQL is that if we are doing so to implement database implementation specific vendor features then it could make our system no longer able to work on all databases.

#### Getting Ready...

This recipe uses the `chapter_04` database that we setup in the chapter introduction and **Phalcon Developer Tools** which we will use to setup a project skeleton.

#### How to do it...
Follow these steps to complete this recipe…

1) We need to have an application skeleton for experimentation.  If you already have such an application, you can skip this step. Create a project skeleton using the "simple" template:
`phalcon project raw-sql simple`.

2) Now point the web browser at the root directory of the project. There should be a page with "Congratulations! ....".  If we see the *Volt directory can't be written* error message then permissions of the directory `app/cache` needs to be changed to allow the web server to write to it.

3) Ensure that your configuration file is setup with the correct host, database, username and password.  This recipe will use the `chapter_04` database that we established in the Chapter 4 introduction.

4) Create three models:

`app/models/Hats`:

```php
<?php

class Hats extends Phalcon\Mvc\Model
{
    public static function findFirstByRelatedColor($colorName)
    {
        $hat = new Hats();
        $color = new Colors();
        $hatColor = new HatsColors();

        $sql = <<<SQL
SELECT Hats.* FROM `{$hat->getSource()}` Hats
INNER JOIN `{$hatColor->getSource()}` HatsColors ON Hats.id = HatsColors.hats_id
INNER JOIN `{$color->getSource()}` Colors ON Colors.id = HatsColors.colors_id
WHERE Colors.name = :colorName
LIMIT 1
SQL;

        $connection = $hat->getReadConnection();
        $row = $connection->fetchOne($sql, Phalcon\Db::FETCH_ASSOC, [
            'colorName' => $colorName
        ]);
        if (!$row) {
            return false;
        }

        return Phalcon\Mvc\Model::cloneResultMap(new Hats(), $row,
            Phalcon\Mvc\Model::DIRTY_STATE_PERSISTENT);
    }


    public static function findByRelatedColor($colorName)
    {
        $hat = new Hats();
        $color = new Colors();
        $hatColor = new HatsColors();

        $sql = <<<SQL
SELECT Hats.*
FROM `{$hat->getSource()}` Hats
INNER JOIN `{$hatColor->getSource()}` HatsColors ON Hats.id = HatsColors.hats_id
INNER JOIN `{$color->getSource()}` Colors ON Colors.id = HatsColors.colors_id
WHERE Colors.name = :colorName
SQL;

        // We are getting the connection service from the model instead of from the
        // DI because each model can define its own connection service.
        $connection = $hat->getReadConnection();
        $results = $connection->query($sql, [
            'colorName' => $colorName
        ]);

        return new Phalcon\Mvc\Model\Resultset\Simple(null, $hat, $results);
    }

}
```

```php
<?php

class Colors extends Phalcon\Mvc\Model
{
}
```

```php
<?php

class HatsColors extends Phalcon\Mvc\Model
{
}
```

5) Create a controller `app/controllers/SqlController.php`

```php
<?php

class SqlController extends Phalcon\Mvc\Controller
{
    public function findFirstByRelatedColorAction()
    {
        $colorName = $this->request->getQuery('color', null, 'black');

        $hat = Hats::findFirstByRelatedColor($colorName);
        $this->response->appendContent($hat->name);
        $this->response->send();
    }

    public function findByRelatedColorAction()
    {
        $colorName = $this->request->getQuery('color', null, 'black');

        $resultset = Hats::findByRelatedColor($colorName);
        foreach ($resultset as $record) {
            $this->response->appendContent($record->name);
            $this->response->appendContent('<br>');
        }
        $this->response->send();
    }

    public function lowLevelAction()
    {
        $colorName = $this->request->getQuery('color', null, 'black');

        $hat = new Hats();
        $color = new Colors();
        $hatColor = new HatsColors();

        $sql = <<<SQL
SELECT Hats.name as hats_name, Colors.name colors_name
FROM `{$hat->getSource()}` Hats
INNER JOIN `{$hatColor->getSource()}` HatsColors ON Hats.id = HatsColors.hats_id
INNER JOIN `{$color->getSource()}` Colors ON Colors.id = HatsColors.colors_id
WHERE Colors.name = :colorName
SQL;

        $dbh = $hat->getReadConnection()
            ->getInternalHandler();
        $stmt = $dbh->prepare($sql);
        $stmt->execute([
            'colorName' => $colorName
        ]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as $row) {
            $this->response->appendContent($row['hats_name'] . ': ' . $row['colors_name']);
            $this->response->appendContent('<br>');
        }

        $this->response->send();
    }

}
```

6) Point the browser at `sql/findByRelatedColor?color=black`

You should see the hats "sombrero" and "fedora" printed out.

7) Point the browser at `sql/findFirstByRelatedColor?color=black`

You should see the hat "sombrero" printed out.

8) Point the browser at `sql/lowLevel`

You should see the hats "sombrero" and "fedora" printed out without their colors.

#### How it works...

In this recipe we demonstrate two approaches to dealing with raw SQL; the first method utilizes as much of the built-in Phalcon infrastructure as possible and actually returns Phalcon models wrapped in a standard Phalcon\Mvc\Model\Resultset\Simple object and the second method goes straight to low-level PDO as soon as possible and returns a PHP array of rows with the fields from two joined tables.

Before we look at the code lets discuss the general approach taken in the two static methods that we declared in our Hats model.

In our Hats model we have created two static methods that are based upon the naming conventions of the static find methods available in Phalcon models.  For example; if we wanted to quickly find only the first Hats model that matched a name then we could call `Hats::findFirstByName($color)`.  This magic PHP method is provided by the Phalcon\Mvc\Model class for each property.  So in this convention we have named our static model functions `findFirstByRelatedColor` and `findByRelatedColor`.  Both of these methods return standard Phalcon framework objects that one would expect after performing a query.

If we wanted then we could implement all of the static `find...` methods and by doing so we could create our own model querying implementation while still using Phalcon\Mvc\Model class as a central repository of business knowledge.  In fact as we will soon see we are already doing a bit of this in our recipe by retrieving the database table name from the model instead of hard coding it into our SQL. Additionally we are also retrieving the database connection directly from the Hats model instead of assuming that it is the typical 'db' service.  This allows us to take advantage of the lesson provided for in other recipes by breaking our models out into one than one database.  In short we can avoid hard coding service related configurations directly into our raw SQL code.

Now lets look at our Hats model code.

In the static method `findFirstByRelatedColor` we start out by instantiating three objects for each of the models that we will use in our SQL.  Even though we will not be using these objects directly in our queries we need to instantiate them to be able to retrieve meta data and specific serviec configuration data from them.

```php
$hat = new Hats();
$color = new Colors();
$hatColor = new HatsColors();
```

Now we will look at the actual generated SQL.  Notice that we are using the heredoc format with hard line returns after each major SQL section as this provides a clean readable format.

```php
    $sql = <<<SQL
SELECT Hats.* FROM `{$hat->getSource()}` Hats
INNER JOIN `{$hatColor->getSource()}` HatsColors ON Hats.id = HatsColors.hats_id
INNER JOIN `{$color->getSource()}` Colors ON Colors.id = HatsColors.colors_id
WHERE Colors.name = :colorName
LIMIT 1
SQL;
```

We are we are calling `getSource()` on each model object so that we can still take advantage of table aliases.  Putting that aside what is left is a typical third normalized SQL join that is beyond the scope of this recipe to discuss in great detail.  We are joining Hats to HatsColor and then finally to Color.  Notice that since we are creating the SQL ourself that we will need to specify the join condition ourselves.  Next we have a single WHERE condition to match the color to the placeholder `:colorName`.

**Note**: If we wanted we could also extract the actual table field names used in the join from conditions from the models meta data.

**Note:** PHQL uses the placeholder style with a colon before and after the name while standard PDO only uses a single colon before the placeholder name.

Next we will discuss the code sequence that queries the database and returns data.

This line will allow us to gain more direct access to the database adapter.

```php
$connection = $hat->getReadConnection();
```

Here `$connection` is not a direct PDO object as it is wrapped in a `Phalcon\Db\Adapter\Pdo` object.  This will be low enough level for us to achieve a lower level approach while still allowing us to take advantage of the Phalcon framework.  In a later example we will demonstrate the most low level approach.

Next we utilize the `fetchOne` method to return a normal PHP array of the results for that model.  Here we must use the `Phalcon\Db::FETCH_ASSOC` constant to inform PDO how we would like the results to be returned.  We also use the third argument to pass in our bind values for the placeholder `:colorName`.

```php
$row = $connection->fetchOne($sql, Phalcon\Db::FETCH_ASSOC, [
    'colorName' => $colorName
]);
```

We will then return false if a matching record could not be found to remain consistent with Phalcon conventions.
```php
if (!$row) {
    return false;
}
```

At this point we know that we have a valid record so we will simply `cloneResultMap` to pass in create a new model with the appropriate flags set.  In this case we want to inform the model that it came from a persistent location so even though we used raw SQL here to retrieve the record we still can use the normal model saving methods to update the database.

```php`
return Phalcon\Mvc\Model::cloneResultMap(new Hats(), $row,
    Phalcon\Mvc\Model::DIRTY_STATE_PERSISTENT);
```

So we finished that method so now lets take a look at `Hats::findByRelatedColor`.  It uses the same setup as `findFirstByRelatedColor` and so we will skip ahead to just look at differnet code at the end.

We will get a `Phalcon\Db\Adapter\Pdo` $connection object the same but this time since we want to retrieve many records we will make a call to the `query` method to retrieve a `Phalcon\Db\Result\Pdo` object.

```php
$connection = $hat->getReadConnection();
$results = $connection->query($sql, [
    'colorName' => $colorName
]);
```

Now we will return the normal simple resultset object with our results.  We will reuse our `$hat` model object for the meta data references since we don't need it for anything else.

```
return new Phalcon\Mvc\Model\Resultset\Simple(null, $hat, $results);
```

Now we are ready to look at `SqlController` to see how we invoked our Hats methods.  In each of these three controller actions we add a nice call to the request service query variable `color` so that we can test out the search with different values.  For example we can search for the red by appending `?color=red` to each corresponding route URL.  In our actions we have filled in the color black as the default.

```php`
public function findByRelatedColorAction()
{
    $colorName = $this->request->getQuery('color', null, 'black');
```

So simply we can retrieve the first Hat that has the matching color with:

```php
$hat = Hats::findFirstByRelatedColor($colorName);
```

and for all Hats matching that color with:

```php
$resultset = Hats::findByRelatedColor($colorName);
```

Finally we will look at a very low level SQL approach in the `lowLevelAction` method.  The initial setup starts the same as the other actions but once we get to the SQL it diverges a lot.

Skipping straight to the SQL.  We see that here that the SQL will return two fields from different tables as so we cannot force this into a Phalcon\Mvc\Model object.

```SQL
$sql = <<<SQL
SELECT Hats.name hats_name, Colors.name colors_name
FROM `{$hat->getSource()}` Hats
INNER JOIN `{$hatColor->getSource()}` HatsColors ON Hats.id = HatsColors.hats_id
INNER JOIN `{$color->getSource()}` Colors ON Colors.id = HatsColors.colors_id
WHERE Colors.name = :colorName
SQL;
```

This time we will go deeper by calling `getInternalHandler()` to return an actual PDO database handler.

```php
$dbh = $hat->getReadConnection()
    ->getInternalHandler();
```

The following is all very standard PDO query code.  Notice that we are passing in `PDO::FETCH_ASSOC` instead of `Phalcon\Db::FETCH_ASSOC` since this direct PDO code is not related to Phalcon.

```php
$stmt = $dbh->prepare($sql);
$stmt->execute([
    'colorName' => $colorName
]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

In this last example we went all of the way to raw SQL and PDO acesss but we were still able to take advantage of Phalcon model definitions to construct the SQL in a way that relies less upon hard coding directly in the SQL.
