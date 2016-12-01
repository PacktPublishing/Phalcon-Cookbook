
## Defining relationships between models

In this recipe we will establish an understanding of relationships between Phalcon\Mvc\Model implementations.  This will allow us to use third normal database form without needing to repeatedly specify primary keys and foreign keys for basic queries.

#### Getting Ready...

This recipe uses the `chapter_04` database that we setup in the chapter introduction and **Phalcon Developer Tools** which we will use to setup a project skeleton.

#### How to do it...
Follow these steps to complete this recipe…

1) We need to have an application skeleton for experimentation.  If you already have such an application, you can skip this step. Create a project skeleton using the "simple" template:
`phalcon project model-relations simple`.

2) Now point the web browser at the root directory of the project. There should be a page with "Congratulations! ....".  If we see the *Volt directory can't be written* error message then permissions of the directory `app/cache` needs to be changed to allow the web server to write to it.

3) Ensure that your configuration file is setup with the correct host, database, username and password.  This recipe will use the `chapter_04` database that we established in the Chapter 4 introduction.

4) Create three models:

`app/models/Hats.php`:

```php
<?php

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

`app/models/Colors.php`

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

`app/models/HatsColors.php`

```php
<?php

class HatsColors extends Phalcon\Mvc\Model
{
}
```

5) Create a Relations controller

`app/controllers/RelationsController`

```php
<?php

class RelationsController extends Phalcon\Mvc\Controller
{
    public function indexAction()
    {
        $this->response->appendContent('<h3>Hats</h3>');
        foreach(Hats::find() as $hat) {
            $this->printHatColors($hat);
        }

        $this->response->appendContent('<h3>Colors</h3>');
        foreach(Colors::find() as $color) {
            $this->printColorHats($color);
        }

        $this->response->send();
    }

    private function printHatColors($hat)
    {
        $colors = $hat->getRelated('colors');

        $this->response->appendContent($hat->name . ':');
        if ($colors->count() > 0) {
            foreach ($colors as $color) {
                $this->response->appendContent(' ' . $color->name);
            }

        } else {
            $this->response->appendContent(' without color');
        }
        $this->response->appendContent('<br>');
    }

    private function printColorHats($color)
    {
        $hats = $color->getHats();

        $this->response->appendContent($color->name . ':');
        if ($hats->count() > 0) {
            foreach ($hats as $hat) {
                $this->response->appendContent(' ' . $hat->name);
            }

        } else {
            $this->response->appendContent(' without hats');
        }
        $this->response->appendContent('<br>');
    }
}
```

6) In the browser goto path `` of the project.  There should be a Hats and Colors header with their relation data printed out for each record.

#### How it works...

In our recipe we have created three models; Hats, Colors and HatsColors.  Two of them are storing actual data (such as their name) and the third model HatsColors stores primary key combinations that Phalcon will use to link a Hat to multiple Colors and also a Colors to multiple Hats.  Notice that we are using the plural form when naming models as this reminds us that we are dealing with a set of data.

First we will look at the Hats model `initialize` function.  This function is called only a single time during execution and it is used to configure the Models Meta Data service with relationship data (??? Is this correct???).  What this code does is link up the Hats primary key field `id` to the HatsColors field `hats_id` and then links the HatsColors field `colors_id`to the Colors primary key field `id`.  Then it assigns a relationship alias named "colors" so that we can easily access the related data by calling it by its name.

```php
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
```

However this only allows us to use use relations a single direction from Hats to Colors and to be able to have the same functionality from the other direction me must also define this relationship in the Colors model.  Notice the strong similarity in the setup.

```php
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
```

Finally in RelationsController we call out to the query functionality that is built into Phalcon\Mvc\Model.

Here we call the static `find` method on our Hats model to receive a Phalcon\Mvc\Model\Resultset\Simple object with our records but for now we don't need to know much about this class other than that it is an object that can be iterated upon to access individual records (models).

```php
foreach(Hats::find() as $hat) {
    $this->printHatColors($hat);
}
```

Now in `printHatColors` function we will retrieve all of the related color records for our Hats model.  Note that we can also pass in additional query parameters to this function but we'll cover that in a later recipe.  So really this is where the magic happens because with this simple call we are able to hide the complexity of a third normal database relationship to obtain all of the related records.

```php
private function printHatColors($hat)
{
    $colors = $hat->getRelated('colors');
```

At this point `$colors` contains all of the Colors records that are related to `$hat`.  Due to the nature of a many-to-many relationship $colors could be of size 0, 1 or many.  The relationship in mathematical terms is not "onto" or "one-to-one" meaning that each hat can have any number of colors and each color can have any number of hats.  Additionally each color may have no hats and each hat may have no colors (as strange as a colorless hat would be in real life).

So now we will iterate through the Phalcon\Mvc\Model\Resultset\Simple `$colors` object in the same way as we iterated through $hats and printing out the results.

Notice that we are using `$this->response->appendContent()` for our output instead of writing directly to standard output with `echo`.  We are allowing the `response` service to handle the output so that HTTP headers and buffers can be automatically used and all of the output correctly inserted into the content area of a Volt layout.

Now we will skip past all of the simple output formatting to the printColorHats function.  This function is a mirror image of the `printHatsColors` function except that it uses the shorthand magic method to obtain the related records.

```php
private function printColorHats($color)
{
    $hats = $color->getHats();
```
