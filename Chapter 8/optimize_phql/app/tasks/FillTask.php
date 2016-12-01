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
