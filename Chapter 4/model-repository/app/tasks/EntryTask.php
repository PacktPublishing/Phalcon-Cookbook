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
        echo "Created entry with ID: " . $entry->id;
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
