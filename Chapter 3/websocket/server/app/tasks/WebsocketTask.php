<?php

class WebsocketTask extends Phalcon\Cli\Task
{
    public function mainAction()
    {
        $server = Ratchet\Server\IoServer::factory(
            new Ratchet\Http\HttpServer(
                new Ratchet\WebSocket\WsServer(
                    new Chat()
                )
            ),
            8080
        );

        $server->run();
    }
}
