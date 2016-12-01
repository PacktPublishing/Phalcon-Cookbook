
## Caching Model Meta data and Query Results for Faster Performance

In this recipe we will detail how to dramatically increase the performance of our website by caching both the models meta data used in the ORM and also the results of specific queries.  These simple Phalcon capabilities will allow us dramatic improvements to our scaling capabilities.

// NOTE!!!  The Model::getRelated query does not cache at this moment and so I have included a work around that I hope we can have removed once the Phalcon code base is improved and so the "How it works" section currently reflects this ideal case.  When Phalcon is improved the work around code will be removed and the ideal code will be uncommented.

#### Getting Ready...

This recipe uses the **Phalcon Developer Tools** for building a project project skeleton and it will require a database.

In this recipe we will need to use the `acpu` PHP entension.

#### How to do it...
Follow these steps to complete this recipe…

1) We need to have an application skeleton for experimentation.  If you already have such an application, you can skip this step. Create a project skeleton using the "simple" template:
`phalcon project cache_data simple`.

2) Create database `cache_data`:

```
CREATE TABLE `links` (
  `id` int(11) NOT NULL,
  `val` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `records` (
  `id` int(11) NOT NULL,
  `val` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `records_links` (
  `id` int(11) NOT NULL,
  `recordsId` int(11) NOT NULL,
  `linksId` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `links`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `records`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `records_links`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `records_links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
```

3) Create model `app/models/Records.php`:

`Records`:

```php
<?php

class Records extends Phalcon\Mvc\Model
{
    public function initialize()
    {
        // TODO: Ideally this would be done automatically from the hasManyToMany relation
        $this->hasMany(
            'id',
            'RecordsLinks',
            'linksId'
        );

        $this->hasManyToMany(
            'id',
            'RecordsLinks',
            'recordsId',
            'linksId',
            'Links',
            'id',
            [
              'alias' => 'links',
            ]
        );
    }
}
```

Create model `app/models/Links.php`:

```php
<?php

class Links extends \Phalcon\Mvc\Model
{
    public function initialize()
    {
        $this->hasManyToMany(
            'id',
            'RecordsLinks',
            'linksId',
            'recordsId',
            'Records',
            'id',
            [
              'alias' => 'records',
            ]
        );
    }
}
```

Create model `app/models/RecordsLinks.php`:

```php
<?php

class RecordsLinks extends Phalcon\Mvc\Model
{
    public function initialize()
    {
        // TODO: Ideally this would be done automatically from the hasManyToMany relation
        $this->belongsTo(
            'linksId',
            'Cache_results\Models\Links',
            'id'
        );
    }
}
```

4) Ensure that our `app/library` directory is included registered autoloader path.  If it isn't then add it to `app/config/loader.php` by adding the directory `$config->application->libraryDir` to a registerDirs call.

5) Add the "modelsCache" service in `app/config/services.php`:

```php
$di->set('modelsCache', function () {
    $frontend = new Phalcon\Cache\Frontend\Data([
        "lifetime" => 86400
    ]);

    $backend = new Phalcon\Cache\Backend\Apc($frontend);

    return $backend;
});
```

6) Add the following to the "db" service in `app/config/services.php`:

```php
$connection->totalQueries = 0;
$databasePlugin = new DatabasePlugin();
$databasePlugin->setDI($this);

$eventsManager = $this->getEventsManager();
$eventsManager->attach('db', $databasePlugin);
$connection->setEventsManager($eventsManager);
```

7) Create a database plugin `app/library/DatabasePlugin.php` for recording the number of queries performed:

```php
<?php

class DatabasePlugin extends \Phalcon\Mvc\User\Plugin
{
    public function afterQuery($event, $connection)
    {
        error_log($connection->getRealSQLStatement());
        $connection->totalQueries++;
    }
}
```

8)
Add controller `app/controllers/FillController.php`:

```php
<?php

class FillController extends Phalcon\Mvc\Controller
{

    public function indexAction()
    {
        $this->fillRecordsTable(10, 3);
    }

    private function fillRecordsTable($neededRecords, $neededLinksPerRecord)
    {
        $recordCount = Records::count();

        $needsToCreateCount = $neededRecords - $recordCount;
        if ($needsToCreateCount == 0) {
            echo "The records were already created.";
            return;
        }

        echo "We need to create $needsToCreateCount records each with $neededLinksPerRecord links." . PHP_EOL;
        echo "Please wait..." . PHP_EOL;

        for ($i = $recordCount; $i < $neededRecords; $i++) {
            $record = new Records([
                'val' => uniqid()
            ]);

            $links = [];
            for ($j = 0; $j < $neededLinksPerRecord; $j++) {
                $links[] = new Links([
                    'val' => uniqid()
                ]);
            }
            $record->links = $links;

            $record->save();

            if ($record->getMessages() !== null) {
                foreach ($record->getMessages() as $message) {
                    echo $message->getMessage() . "\n";
                }
            }
        }

        echo "Records were created.  Please run again to see memory usage." . PHP_EOL;
    }
}
```

9) Add controller `app/controllers/TestController.php`:

```php
<?php

class TestController extends Phalcon\Mvc\Controller
{
    public function indexAction()
    {
        $this->startTime = microtime(true);

        $records = Records::find();
        foreach ($records as $record) {
            $this->response->appendContent('<p>');
            $this->response->appendContent($record->id . ': ' . $record->val . '<br>');

            $links = $record->getLinks();

            foreach ($links as $link) {
                $this->response->appendContent('-- ' . $link->id . ': ' . $link->val . '<br>');
            }

            $this->response->appendContent('</p>');
        }

        $totalTime =  microtime(true) - $this->startTime;

        $this->response->appendContent('<div>total time: ' . round($totalTime, 5) . " seconds.</div>");
        $this->response->appendContent('<div>total queries: ' . $this->db->totalQueries . "</div>");

        $this->response->send();
    }

    public function cacheAction()
    {
        $this->getDI()->setShared('modelsMetadata', function () {
            return new Phalcon\Mvc\Model\MetaData\Apc([
                "lifetime" => 86400,
                "prefix"   => "cache_data"
            ]);
        });

        $this->startTime = microtime(true);

        $records = Records::find([
            'cache' => [
                'key' => 'all-records',
                'lifetime' => 15
            ]
        ]);
        foreach ($records as $record) {
            $this->response->appendContent('<p>');
            $this->response->appendContent($record->id . ': ' . $record->val . '<br>');

            $cache = $this->getDI()
                ->getModelsCache();

            $cacheKey = 'record-links-' . $record->id;
            $links = $cache->get($cacheKey);
            if (!$links) {
                $phql = <<<PHQL
SELECT Links.* FROM Links
JOIN RecordsLinks ON Links.id = RecordsLinks.linksId
JOIN Records ON Records.id = RecordsLinks.recordsId
WHERE Records.id = :recordsId:
PHQL;

                $query = new \Phalcon\Mvc\Model\Query($phql, $this->getDI());
                $links = $query->execute([
                    'recordsId' => $record->id,
                ]);

                $cache->save($cacheKey, $links);
            }

            // FIXME: We should be just doing this.
            // $links = $record->getLinks([
            //     'cache' => [
            //         'key' => 'record-links-' . $record->id,
            //         'lifetime' => 100
            //     ]
            // ]);

            foreach ($links as $link) {
                $this->response->appendContent('-- ' . $link->id . ': ' . $link->val . '<br>');
            }

            $this->response->appendContent('</p>');
        }

        $totalTime =  microtime(true) - $this->startTime;

        $this->response->appendContent('<div>total time: ' . round($totalTime, 8) . " seconds.</div>");
        $this->response->appendContent('<div>total queries: ' . $this->getDI()->getDb()->totalQueries . "</div>");

        $this->response->send();
    }
}
```

In the browser in the root path of the project go to the following paths:
* `/fill` to generate the required records
* `/test`
* `/test/cache`
* `/test/cache` - Visit the page a second time

After the first time of visiting `/test/cache` the models meta data and the query results will already be stored in the APC cache and so the total time involved should be dramatically better than `/test`.

#### How it works...

In this recipe we are benefiting from utilizing the APC cache for two different expensive operations.  The models meta data cache is an easy fit for caching because it rarely changes except during development if the model schemas are being changed and otherwise they will not change.  The models meta data cache performs several data queries as well as needing to process that data and by caching this we can save significant time.  Finally we are caching the results of individual queries and this can be quite a time saver as well but it is not appropriate for queries that could be rapidly changing.  In this recipe we are assuming that our query results will be fresh for a while.

Lets look at our models.  In this case we are using a typical hasManyToMany relationship between Links and Records that uses the RecordsLinks model to join the two together.  In this recipe we are looking to cache expensive queries to demonstrate time savings and so we are using the results of table joins rather than simply retrieving records from a single table.

The first thing that we'll want to do is to populate our database with linked data via the FillController.  This is very standard record creation stuff but we do show off how easy it is to have Phalcon do the hard work of creating the intermediate linked table (which is `RecordsLinks` in our case).

We simply loop through to create the needed number of records while attaching an array of Links to our relationship alias property so that Phalcon can do the heavy relationship work for us.

```php
for ($i = $recordCount; $i < $neededRecords; $i++) {
    $record = new Records([
        'val' => uniqid()
    ]);

    $links = [];
    for ($j = 0; $j < $neededLinksPerRecord; $j++) {
        $links[] = new Links([
            'val' => uniqid()
        ]);
    }
    $record->links = $links;

    $record->save();
}
```

Now that we have our dataset we'll look at our "modelsCache" service that is used for storing results:

First we will create a front end for our data.  In this case the typical Data adapter is what we will only be interacting with our PHP environment.

```php
$frontend = new Phalcon\Cache\Frontend\Data([
    "lifetime" => 86400
]);
```

For our backend we will be using the well established APC storage mechanism to allow the data to persist across PHP processes.  This is a very simple and fool proof backend that is easy to work with and doesn't require an additional server technology to use.

```php
$backend = new Phalcon\Cache\Backend\Apc($frontend);
```

Alternatively we could use a Redis or Memcache backend that would be more appropriate for a massive scaled out system but has the downside of adding the overhead of TCP/IP communication.

Next we will add a database service plugin that will record all of the queries performed.  This is a rather typical plugin setup except that we are hacking in a total queries counter directly onto the connection itself.

```php
$connection->totalQueries = 0;
$databasePlugin = new DatabasePlugin();
// ...
```

Now we will test the completion time of iterating through each Record and displaying out each Record's related Links.


Lets first look at an abbreviated version of the uncached `indexAction` method.  In this method we have removed the lines that output text to only focus on the loops and queries.

```php
public function indexAction()
{
    $this->startTime = microtime(true);

    $records = Records::find();
    foreach ($records as $record) {
        $links = $record->getLinks();

        foreach ($links as $link) {
            // Output
        }
    }

    $totalTime =  microtime(true) - $this->startTime;
}
```

Now lets look at the fully cached version of that method.  Notice that at the very top we are first redefining the typical "modelsMetadata" service to store the data using the APC persistent mechanism.  Although it is not recommended to ever define services within a controller like this it works out perfectly for our example because when the program execution reaches this point in the code we have not yet called out to database for our models meta data.

```php
public function cacheAction()
{
    $this->getDI()->setShared('modelsMetadata', function () {
        return new Phalcon\Mvc\Model\MetaData\Apc([
            "lifetime" => 86400,
            "prefix"   => "cache_data"
        ]);
    });

    $this->startTime = microtime(true);

    $records = Records::find([
        'cache' => [
            'key' => 'all-records',
            'lifetime' => 15
        ]
    ]);
    foreach ($records as $record) {
        $links = $record->getLinks([
            'cache' => [
                'key' => 'record-links-' . $record->id,
                'lifetime' => 100
            ]
        ]);
    }

    $totalTime =  microtime(true) - $this->startTime;
}
```

Now the first time that the `cacheAction` method is called (through the `/test/cache` path) the amount of time needed should be roughly equal to that of the cached version.  However any subsequent request should be have a dramatically reduced amount of time for some non-trivial time and CPU savings.
