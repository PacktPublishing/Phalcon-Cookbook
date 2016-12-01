<?php

class DatabasePlugin extends \Phalcon\Mvc\User\Plugin
{
    public function afterQuery($event, $connection)
    {
        error_log($connection->getRealSQLStatement());

        $connection->totalQueries++;
    }
}
