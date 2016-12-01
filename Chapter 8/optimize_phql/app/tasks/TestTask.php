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
