<?php

class ReconnectPlugin extends Phalcon\Mvc\User\Plugin
{

    public function initializeConnection($connection)
    {
        // Make sure that the query cache is off.
        $connection->query('SET QUERY_CACHE_TYPE = OFF;');

        // Ask the database how many seconds the timeout is set at
        $result = $connection
            ->query("SHOW VARIABLES LIKE 'wait_timeout'")
            ->fetchArray();

        $timeout = (int) $result['Value'];
        if ($timeout > 5) {
            error_log('Hey! We need to set the timeout to five seconds so that we can test the timeout reconnect.');
        }
        $connection->timeout = $timeout;

        // Set the connection start time and the timeout duration
        $connection->start = time();
    }

    public function beforeQuery(Phalcon\Events\Event $event, $connection)
    {
        // We want to return if we are running our test calculation query.
        if ($connection->getSQLStatement() == 'SELECT 1+2+3') {
            return;
        }

        // If the connection duration is past the timeout amount then we will reconnect.
        $activeTimeout = time() - $connection->start;
        if ($activeTimeout > $connection->timeout) {
            error_log("First attempt to reconnect.");
            $connection->connect();
            $connection->start = time();
        }

        try {
            // We'll issue a simply query that doesn't require any data.
            $result = $connection
                ->query('SELECT 1+2+3')
                ->fetch();

            // If the result is not correct then we will try to reconnect again.
            if ($result[0] != 6) {
                error_log("Second attempt to reconnect.");
                $connection->connect();
            }
        } catch (\PDOException $e) {

            // If this was unsuccesful then we will try one last time to reconnect.
            error_log("Third attempt to reconnect.");
            $connection->connect();
        }
    }
}
