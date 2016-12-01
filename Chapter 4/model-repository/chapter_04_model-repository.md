
## Using models as information repositories

In this recipe we will explore how to use model events to tie additional data to our models outside of the normal database service layer.  The approach is this recipe can be adapted to handling file uploads, sending emails or any other activity that exceeds the typical "store everything in the database" technique.

#### Getting Ready...

This recipe uses is own database schema and **Phalcon Developer Tools** which we will use to setup a CLI project skeleton.  This project will require command line usage.

This recipe will use the `openssl_random_pseudo_bytes` function to create UUIDs (Universally Unique Identifier) and so you will need to ensure that this is enabled in the PHP configuration.  On Linux and OSX this should already be enabled but on Windows with environments like WAMP (...) it may require manually enabling it in the php.ini file and then restarting the web server.

#### How to do it...
Follow these steps to complete this recipe…

1) We need to have an CLI application skeleton for experimentation.  If you already have such an application, you can skip this step. Create a project skeleton using the "cli" template to create a command line application:
`phalcon project model-repository cli`.

2) Create the database `model-repository` and import the following into it:

```SQL
CREATE TABLE `entries` (
  `id` int(11) NOT NULL,
  `uuid` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `entries`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
```

3) The "cli" project skeleton does not include a database service and so we will need to add it to our services file.  Add the following to `app/config/services.php`:

```php
$di->setShared('db', function () use ($config) {
    $dbConfig = $config->database->toArray();
    $adapter = $dbConfig['adapter'];
    unset($dbConfig['adapter']);

    $class = 'Phalcon\Db\Adapter\Pdo\\' . $adapter;

    return new $class($dbConfig);
});
```

4) Add the following to the main config file for the project.  In the cli skeleton it is located at `app/config/config.php`.

```php
'database' => [
    'adapter'     => 'Mysql',
    'host'        => 'localhost',
    'username'    => 'root',
    'password'    => 'root',
    'dbname'      => 'model-repository',
    'charset'     => 'utf8',
],
'application' => [
    'dataDir'        => realpath(__DIR__ . '/../data')
]
```

Notice that we added a 'dataDir' path to our project.  Later we will be storing model data in this location.

5) Create the Entries model at `app/models/Entries.php`

```php
<?php

class Entries extends Phalcon\Mvc\Model
{
    public $id;

    public $uuid;

    private $value;

    public function getValue()
    {
        return $this->value;
    }

    protected function retrieveValue()
    {
        $deepDirectory = $this->getDeepDirectory();
        if (!file_exists($deepDirectory)) {
            throw new \Exception('The deep directory does not exist.');
        }

        return file_get_contents($deepDirectory . '/' . $this->uuid);
    }

    protected function getDeepDirectory()
    {
        $dataDir = $this->getDI()
            ->getConfig()
            ->application->dataDir;

        $deepDir = $dataDir;
        for ($i = 0; $i < 3; $i++) {
            $deepDir .= '/' . $this->uuid[$i];
        }
        return $deepDir;
    }

    protected function beforeValidationOnCreate()
    {
        $data = openssl_random_pseudo_bytes(16);
        $base16 = bin2hex($data);
        $base62 = gmp_strval(gmp_init($base16, 16), 62);
        $padded = str_pad($base62, 22, '0', STR_PAD_LEFT);
        $this->uuid = vsprintf('%s%s%s-%s%s%s%s%s-%s%s%s', str_split($padded, 2));
    }

    protected function afterCreate()
    {
        $deepDirectory = $this->getDeepDirectory();
        if (!file_exists($deepDirectory)) {
            mkdir($deepDirectory, 0770, true);
        }
        file_put_contents($deepDirectory . '/' . $this->uuid, rand(1, 1000));
    }

    protected function afterDelete()
    {
        // See if you can do this one on your own.
        // Make sure to please not recursively delete your
        // entire file system, project or data folder!!!
    }

    protected function afterFetch()
    {
        $this->value = $this->retrieveValue();
    }

    protected function initialize()
    {
        $this->skipAttributes(['value']);
    }
}
```

6) Create the Entry task at `app/tasks/EntryTask.php`

```php
<?php

class EntryTask extends \Phalcon\Cli\Task
{
    public function addAction()
    {
        $entry = new Entries();
        if (!$entry->create()) {
            foreach ($entry->getMessages() as $message) {
                error_log($message);;
            }
            return false;
        }
        echo "Created entry with id: " . $entry->id;
    }

    public function getAction($argv)
    {
        if (!isset($argv[0]) || !is_numeric($argv[0])) {
            throw new \Exception('This action requires a integer argument.');
        }
        $id = $argv[0];

        $entry = Entries::findFirstById($id);
        if (!$entry) {
            fwrite(STDERR, 'An entry with that ID could not be found.');
            return false;
        }

        echo 'Retrieving stored value: ' . $entry->getValue();
    }
}
```

7) Open up the command line terminal and change into the recipe project directory and enter in the following command:
`./run`
You should see: "Congratulations! You are now flying with Phalcon CLI!".

8) Now execute:
`./run entry add`

You should see "Created entry with ID: SOME_ID"

9) Use the value that was returned in the place of SOME_ID for the next line:

Now enter the execute:
`./run entry get SOME_ID`

You should see "Retrieving stored value: SOME_VALUE" where SOME_VALUE is an integer value that was stored and retrieved.

#### How it works...

In this recipe we'll skip past the basic configuration since this recipe is more advanced and we'll start with our Entries model where most of the interesting things happen.

The model has three properties and we have declared $value as private because it is not being stored in the database and we are using the getter function getValue to retrieve it.  This helps us to clarify that this property cannot be changed through the normal mechanism and that it is dependent upon other factors.

```php
public $id;

public $uuid;

private $value;

public function getValue()
{
    return $this->value;
}
```

Next we'll look at the model event methods "beforeValidationOnCreate", "afterCreate" and "afterFetch".  These events are triggered during the execution cycle of model actions.

In this method we are creating a UUID value automatically when a model is created.  Since PHP doesn't provide nice UUID creation functions we need to create one ourself.  It is necessary that this is done in "beforeValidationOnCreate" instead of "beforeCreate" since otherwise Phalcon will complain that a field has a null value.  So we create the UUID value as soon as possible and then we could even check that the value is valid during the validation phase.

```php
protected function beforeValidationOnCreate()
{
    $data = openssl_random_pseudo_bytes(16);
    $base16 = bin2hex($data);
    $base62 = gmp_strval(gmp_init($base16, 16), 62);
    $padded = str_pad($base62, 22, '0', STR_PAD_LEFT);
    $this->uuid = vsprintf('%s%s%s-%s%s%s%s%s-%s%s%s', str_split($padded, 2));
}
```

Next we will perform an action after the model has been successfully added to the database.  Notice the `getDeepDirectory` method that we use to return a file storage directory that is unique to this record instance.  We will investigate this method soon.  Finally we create a random integer between 1 and 1000 that we then store in a directory with the UUID as the filename.

```php
protected function afterCreate()
{
    $deepDirectory = $this->getDeepDirectory();
    if (!file_exists($deepDirectory)) {
        mkdir($deepDirectory, 0770, true);
    }
    file_put_contents($deepDirectory . '/' . $this->uuid, rand(1, 1000));
}
```

At this point we have arrived at the point of this recipe.  We are able to store information outside of the normal database by using model events or what is sometimes known as "hooks".  In this recipe we aren't doing anything particularly meaningful since we are simply storing a random integer but this data could more important things such as a filename md5 hash or we could use the hook to send an email or to log a message.

Finally in the `afterFetch` method we retrieve the random value that is stored within the file system.  This is model event is fired automatically after any normal Phalcon model query.  What is really nice here is that this can also be used when using raw SQL as covered in a previous recipe!  So with that said it becomes obvious that using the infrastructure of Phalcon models to organize all of your data is the way to go.

```php
protected function afterFetch()
{
    $this->value = $this->retrieveValue();
}
```

Finally in our initialize method we inform the model that our "value" property is not part of the normal persistent storage and so it should be ignored.

```php
protected function initialize()
{
    $this->skipAttributes(['value']);
}
```

Now lets look at the interesting `getDeepDirectory` method where we create a nested directory based off of the first characters of the UUID.  This is done to prevent a large performance issue that occurs when thousands of files are stored in a single directory since it takes a while to index all of them.  Using this approach dramatically more files can be stored while keeping the filesystem performant.

First we start off accessing the data directory from the configuration service.  It is recommended that any custom directory paths be added to the config service instead of hard coded into the application.

```php
    $dataDir = $this->getDI()
        ->getConfig()
        ->application->dataDir;
```

Next we are create the nested directory path.  So say that our UUID started with "1zA..." then this directory fragment would become "1/z/A".  So the number of files in a single directory will be 62^3 less than without this approach.

```php
    $deepDir = $dataDir;
    for ($i = 0; $i < 3; $i++) {
        $deepDir .= '/' . $this->uuid[$i];
    }
    return $deepDir;
}
```

Now lets look at our Entry task in `app/tasks/Entry.php`:

This is actually pretty simple.  We are creating an entry model and then we try to save it and if it fails then we output the error messages and if it succeeds then we output the primary key of the entry so that we can use it in the getAction method.

```php
public function addAction()
{
    $entry = new Entries();
    if (!$entry->create()) {
        foreach ($entry->getMessages() as $message) {
            error_log($message);;
        }
        return false;
    }
    echo "Created entry with ID: " . $entry->id;
}
```

Lets look at `getAction`:

First we do a few checks to make sure that we have valid arguments.  This is a nice addition to make the program a bit more professional.

```php
    if (!isset($argv[0]) || !is_numeric($argv[0])) {
        throw new \Exception('This action requires a integer argument.');
    }
    $id = $argv[0];
```

Now we look up the entry with that ID and if it doesn't exist we will display a nice error message.  Notice that this is not exceptional behavior and so we are simply outputting to standard error instead of throwing an exception.

````php
    $entry = Entries::findFirstById($id);
    if (!$entry) {
        fwrite(STDERR, 'An entry with that ID could not be found.');
        return false;
    }
```

Finally we get the value for that entry that was stored in the "deep directory".  We now have an information repository that transcends the database.

```php
    echo 'Retrieving stored value: ' . $entry->getValue();
```

**Extra Activities:** See if you can create an algorithm to delete directories using the "afterDelete" model event.  As we currently left it the files will not be removed when the corresponding Entry model is deleted.  Be careful though not to recursively delete your entire project, root filesystem or all of the other files.  You may apply this approach to creating your own powerful file uploading strategy upon the Phalcon framework.
