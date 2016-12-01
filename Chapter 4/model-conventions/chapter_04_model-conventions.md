
## Applying your own naming conventions to models

In this recipe we will introduce workarounds for instances when we are not in control of the column and field naming used in our database schema.  This situation can arise when the system is already under the constraints of other development environments or when working with an existing legacy system.

#### Getting Ready...

This recipe uses the `chapter_04` database that we setup in the chapter introduction and **Phalcon Developer Tools** which we will use to setup a project skeleton.

#### How to do it...
Follow these steps to complete this recipe…

1) We need to have an application skeleton for experimentation.  If you already have such an application, you can skip this step. Create a project skeleton using the "simple" template:
`phalcon project model-conventions simple`.

2) Now point the web browser at the root directory of the project. There should be a page with "Congratulations! ....".  If we see the *Volt directory can't be written* error message then permissions of the directory `app/cache` needs to be changed to allow the web server to write to it.

3) Ensure that your configuration file is setup with the correct host, database, username and password.  This recipe will use the `chapter_04` database that we established in the Chapter 4 introduction.

4) Create two models

`app/models/Hats.php`
 ```php
 <?php

 class Hats extends Phalcon\Mvc\Model
 {
 }

 ```

`app/models/Cars.php`
```php
<?php

class Cars extends Phalcon\Mvc\Model
{
    public function getSource()
    {
        return "cars_legacy123";
    }

    public function columnMap()
    {
        return [
            'id'             => 'id',
            'name_legacy123' => 'name'
        ];
    }
}
```

5) Set `app/controllers/IndexController.php` to the following:

```php
<?php

class IndexController extends ControllerBase
{
    public function indexAction()
    {
        $sombrero = Hats::findFirstByName('sombrero');
        $porsche = Cars::findFirstByName('porsche');

        return $sombrero->name . ' - ' . $porsche->name;
    }
}
```

6) In the browser go to the root path of the project.  You should see "sombrero - porsche"

#### How it Works...

Our model in `app/models/Hats.php` is about as simple as it could be:

```php
class Hats extends Phalcon\Mvc\Model {}
```

In an ideal case this is all that is needed for Phalcon to be able to use the database table for querying, inserting and deleting records.  Although we can create class variables for each table field it is unnecessary for this recipe because the models meta data is obtained from the table.  Its easy!

Our next Cars model is a bit of a tragic case.  We have inherited a schema in which the table and column names are just awful and because it is part of a legacy system we can't just rename them.  We are not out of luck however because Phalcon allows both the table name and field names to be defined independently from what is stored in the database.

First we will set the table source to allow Phalcon to find the data:

```php
    public function getSource()
    {
        return "cars_legacy123";
    }
```

Next we will will map the columns from the database to our ideal names that we would like to work with from within Phalcon.

```php
    public function columnMap()
    {
        return [
            'id'             => 'id',
            'name_legacy123' => 'name'
        ];
    }
```

Now we will go to our code in `app/controllers/IndexController.php`.  The specifics of how this works will be described in a later recipe but simply we are able to search the database for the records by their name.

```php
$sombrero = Hats::findFirstByName('sombrero');
$porsche = Cars::findFirstByName('porsche');
```
