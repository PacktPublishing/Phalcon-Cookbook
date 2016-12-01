<?php

class Chat implements Ratchet\MessageComponentInterface
{
    protected $clients;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(Ratchet\ConnectionInterface $conn)
    {
        $this->clients->attach($conn);

        echo "New connection! ({$conn->resourceId})\n";

        foreach ($this->clients as $client) {
            if ($conn !== $client) {
                // Send connection message to all clients but the current one
                $client->send("Client {$client->resourceId} connected.");
            }
        }
    }

    public function onMessage(Ratchet\ConnectionInterface $from, $msg)
    {
        // Send to all clients but the current one
        foreach ($this->clients as $client) {
            if ($from != $client) {
                $msg = "Client {$client->resourceId} says \"$msg\"";
                $client->send($msg);
            }
        }
    }

    public function onClose(Ratchet\ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
    }

    public function onError(Ratchet\ConnectionInterface $conn, \Exception $e)
    {
        $conn->close();
    }
}
