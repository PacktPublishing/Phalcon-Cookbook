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
