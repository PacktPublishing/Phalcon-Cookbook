
## Optimizing PHQL queries

PHQL is by default one of the fastest ORM technologies available in PHP but even then there are things that need to be understood if one wishes to achieve the pinnacle of efficiency.  In this recipe we will first start with the least efficient approach and then we will progress to two increasingly faster and more memory efficient approaches.  The first stage of this recipe will involve generating the records and its linked data in the database.  Once we have created these records then we can perform the three tests to see how they measure up.

#### Getting Ready...

This recipe uses the **Phalcon Developer Tools** which we will use to setup a project skeleton.  We will need a database connection for this recipe.

#### How to do it...
Follow these steps to complete this recipe…

1) We need to have an application skeleton for experimentation.  If you already have such an application, you can skip this step. Create a project skeleton using the "cli" template:

`phalcon project optimize_phql cli`.

2) Create the database `optimize_phql`

```
CREATE TABLE `links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recordsId` int(11) NOT NULL,
  `uniq` varchar(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


CREATE TABLE `records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uniq` varchar(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
```

3)

Create model `app/models/Links.php`:

```php
<?php

class Links extends Phalcon\Mvc\Model
{
    protected function initialize()
    {
    }
}
```

Create model `app/models/Records.php`:

```php
<?php

class Records extends Phalcon\Mvc\Model
{
    protected function initialize()
    {
        $this->hasMany('id', 'Links', 'recordsId', [
            'alias' => 'Links'
        ]);
    }
}
```

4)

Create task `app/tasks/FillTask.php`:

```php
<?php

class FillTask extends \Phalcon\Cli\Task
{

    public function mainAction()
    {
        $this->fillRecordsTable(2000, 3);
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
                'uniq' => uniqid()
            ]);
            $record->create();

            for ($j = 0; $j < $neededLinksPerRecord; $j++) {
                $link = new Links([
                    'recordsId' => $record->id,
                    'uniq'      => uniqid()
                ]);
                $link->create();
                unset($link);
            }

            unset($record);
        }

        echo "Records were created.  Please run again to see memory usage." . PHP_EOL;
    }
}
```

Create task `app/tasks/TestTask.php`:

```php
<?php

class TestTask extends \Phalcon\Cli\Task
{
    public function mainAction()
    {
        $this->efficiency1Action();
    }

    public function efficiency1Action()
    {
        $this->startTime = microtime(true);

        $records = Records::find();
        foreach ($records as $record) {
            echo $record->id . ': ' . $record->uniq . PHP_EOL;

            $links = Links::find("recordsId = {$record->id}");
            foreach ($links as $link) {
                echo "  " . $link->id . ': ' . $link->uniq . PHP_EOL;
            }
        }

        $this->printStats();
    }

    public function efficiency2Action()
    {
        $this->startTime = microtime(true);

        $records = Records::find();
        foreach ($records as $record) {
            echo $record->id . ': ' . $record->uniq . PHP_EOL;

            $links = Links::find([
                "recordsId = :recordsId:",
                'bind' => [
                    'recordsId' => $record->id
                ]
            ]);
            foreach ($links as $link) {
                echo "  " . $link->id . ': ' . $link->uniq . PHP_EOL;
            }
        }

        $this->printStats();
    }

    public function efficiency3Action()
    {
        $this->startTime = microtime(true);

        // One way to setup a query
        $records = $this->getDI()
            ->getModelsManager()
            ->createQuery("SELECT id, uniq FROM Records")
            ->execute();

        foreach ($records as $record) {
            echo $record->id . ': ' . $record->uniq . PHP_EOL;

            // A second way to setup a query
            $query = new Phalcon\Mvc\Model\Query("SELECT id, uniq FROM Links WHERE recordsId = :recordsId:", $this->getDI());
            $links = $query->execute([
                'recordsId' => $record->id
            ]);

            foreach ($links as $link) {
                echo "  " . $link->id . ': ' . $link->uniq . PHP_EOL;
            }
        }

        $this->printStats();
    }

    protected function printStats()
    {
        echo PHP_EOL;

        $totalTime =  microtime(true) - $this->startTime;
        $peakMegaBytes = memory_get_peak_usage() / (1024 * 1024);

        echo 'peak memory: ' . round($peakMegaBytes, 3) . " MB" . PHP_EOL;
        echo 'total time: ' . round($totalTime, 3) . " seconds.";
    }

}
```

5) On the command line execute `./run fill` and then wait until the database has been filled.

6) Execute the following tests;

* `./run test efficiency1`
* `./run test efficiency2`
* `./run test efficiency3`

#### How it works...

We created two models Records and Links and we have added a relationship from Records to Links while ignoring the relationship from the other direction since it is not needed for this recipe.

This relationship in Records allows us to quickly all Links models that points from Links `recordsId` to Records `id`.

```
$this->hasMany('id', 'Links', 'recordsId', [
    'alias' => 'Links'
]);
```

To start our experiment we first must run the `FillTask` with the command `./run fill` and this will create 2000 Records with 3 Links attached to each one.  We will only quickly go over the code in the FillTask since this is handled in great detail in the "Dealing with Data chapter".

When running `FillTask` we iterate in a for loop to create the necessary number of Records entries.

```
for ($i = $recordCount; $i < $neededRecords; $i++) {
    $record = new Records([
        'uniq' => uniqid()
    ]);
    $record->create();
``

Then we will create and attach 3 Links models to each Records.

```php
for ($j = 0; $j < $neededLinksPerRecord; $j++) {
    $link = new Links([
        'recordsId' => $record->id,
        'uniq'      => uniqid()
    ]);
    $link->create();
    unset($link);
}
```

By the end of this we will have 2000 `Records` models each with 3 `Links` models pointed to each `Records`.  This will give us a large enough data set for our experimentation.

Now that we have our data we will run our first test in `TestTask::efficiency1` by executing `./run test efficiency1`.

```php
$records = Records::find();
foreach ($records as $record) {
    echo $record->id . ': ' . $record->uniq . PHP_EOL;

    $links = Links::find("recordsId = {$record->id}");
    foreach ($links as $link) {
        echo "  " . $link->id . ': ' . $link->uniq . PHP_EOL;
    }
}
```

The problem with `efficiency1` is that we are including the literal values in the PHQL statement and so each time that we make a query Phalcon will need to create and cache a new PHQL statement.

Now we will look at `TestTask::efficiency2` by executing `./run test efficiency2`.

```php
$records = Records::find();
foreach ($records as $record) {
    echo $record->id . ': ' . $record->uniq . PHP_EOL;

    $links = Links::find([
        "recordsId = :recordsId:",
        'bind' => [
            'recordsId' => $record->id
        ]
    ]);
    foreach ($links as $link) {
        echo "  " . $link->id . ': ' . $link->uniq . PHP_EOL;
    }
}
```

We can see that that `efficiency2` is almost the same as `efficiency1` but the main difference being that the values are bound to the query using the `bind` key and that we use a placeholder `:recordsId:` for this value.  This increases our performance by quite a bit but we can still do better.

Now we will look at `TestTask::efficiency3` by executing `./run test efficiency3`.  The main difference in this approach compared to the previous two is that we are now only retrieving individual fields.

Notice that we are only requesting individual fields `id` and `uniq` instead of asking for `*` (all fields).  This allows us to retrieve a `Phalcon\Mvc\Model\Resultset\Complex` object instead of `Phalcon\Mvc\Model\Resultset\Simple` and by doing so we will avoid creating a `Phalcon\Mvc\Model` object for each database row.

```php
// One way to setup a query
$records = $this->getDI()
    ->getModelsManager()
    ->createQuery("SELECT id, uniq FROM Records")
    ->execute();
```

Next we will iterate through our record results and perform a query to obtain each related Links.  This time we will perform a query by directly creating a `Phalcon\Mvc\Model\Query` instead of obtaining it through the 'modelsManager' service.  This is just another way to start a query and both approaches will yield the same results.

Notice that this time we are also only asking for individual fields and this will save the processing time of creating full objects when we only need specific values from our results.

```php
foreach ($records as $record) {
    echo $record->id . ': ' . $record->uniq . PHP_EOL;

    // A second way to setup a query
    $query = new Phalcon\Mvc\Model\Query("SELECT id, uniq FROM Links WHERE recordsId = :recordsId:", $this->getDI());
    $links = $query->execute([
        'recordsId' => $record->id
    ]);

    foreach ($links as $link) {
        echo "  " . $link->id . ': ' . $link->uniq . PHP_EOL;
    }
}
```

This recipe demonstrates the overhead of a full ORM layer as well as the overhead in creating new queries.  While the full ORM layer is valuable and useful it does come with some of its own trade offs.
