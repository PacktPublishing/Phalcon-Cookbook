
## Creating a Ratchet Websocket server

The Websockets API allows for an entirely new type of web application that until recently was only possible with a normal system application or by using the flash browser plugin.  In essence the two most important aspects of a Websocket connection between the client and server is the bidirectional communication and the elimination of the need to transmit HTTP headers on every communication.  Previously when using only web standards (without flash) the only option to retrieve constantly updated content was to "long poll" the server by looping AJAX requests on a timer.

This approach doesn't scale very well at all due to the overhead of the web server request handling, spinning up PHP process, handling the routing and authentication and the HTTP headers that need to be sent for each update.  Additionally developers would need to set an arbitrary delay between AJAX polls and then ultimately this would cause one of the following two problems; if the delay was set too long then the user would need to wait too long for updated data and if the delay was too short then it would cause additional burden on the server.  Actually in any case repeatedly polling the server is very hard on it and it becomes impractical and expensive with a large user base.

This makes Websockets the only serious option for a chat application, game or anything else requiring real time communication.  Websockets are the technology that we need and deserve.

However with this new capability comes an increase in the complexity of our application due to the need to run an additional Websocket server process.  This recipe is a bit different than most others in that we will be creating two separate projects that will interact with each other.  We will be doing a lot including; dabbling in Javascript, installing third-party components and creating a command line server program as well as the expected web application.

You may read about the Websockets API at: https://developer.mozilla.org/en-US/docs/Web/API/WebSockets_API


#### Getting Ready...

To use and test this recipe you will need to have a project skeleton with a CLI (Command Line Application) interface and another project skeleton for normal web server usage.  While an established project could very well have both of these capabilities we will be using two different project skeletons that will each be responsible for a different aspect of this technology stack.  We will need to create two scaffolds with the **Phalcon Developer Tools**.  Additionally in order to install third-party dependencies necessary for this project we will need to install **Composer**.

A database is not required for this recipe.

#### How to do it...
Follow these steps to complete this recipe…

1) We need to install Composer to easily install the third party libraries for enabling Websockets on PHP.  You may get get the open source Composer tool at: https://getcomposer.org/

2) We will need to create two project scaffolds; one with a CLI bootstrap and another with a normal web bootstrap.  We will create the CLI scaffold with `phalcon project server cli` and the web front end scaffold with `phalcon project frontend simple`.

##### Setup the server scaffold

3) Start up a command line terminal shell and change directory into the "server" directory.

* Install the Ratchet library with `composer require cboden/ratchet:0.4.x-dev`.
* Configure the scaffold to use our installed Composer packages by opening up the file `app/cli.php` and adding the line `include __DIR__ . '/../vendor/autoload.php';` directly below the namespace "use" directives.

4)
* Create the directory `app/library`
* Change `app/config/loader.php` to include the library directory by changing it to:

```php
<?php

$loader = new \Phalcon\Loader();
$loader->registerDirs([
    __DIR__ . '/../tasks',
    __DIR__ . '/../library'
]);
$loader->register();
```

Create the file `app/library/Chat.php`:

```php
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
```

5) Create `app/tasks/WebsocketTask.php`:

```php
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
```

##### Setup the frontend scaffold

6) Change directory into the frontend scaffold and make sure that the webserver can write to the `app/cache` directory.

7) Create controller `app/controllers/WebsocketController.php`

```php
<?php

class WebsocketController extends Phalcon\Mvc\Controller
{
    public function indexAction()
    {
    }
}
```

Notice that the controller appears to not do anything.  We will be adding our functionality to a view that will be included in the HTML output when the index action is executed.

Create view `app/views/websocket/index.volt`

```
<h1>Websocket Chat Server</h1>

<script>
// Note: You may need to change the domain name and host port depending
//       upon how you setup your testing system.
var conn = new WebSocket('ws://localhost:8080');

conn.addEventListener('open', function(e) {
  console.log("Connection established!");
  helloWorldPing()
});

function helloWorldPing() {
  if (conn.readyState === conn.OPEN) {
    conn.send('Hello World!');
    setTimeout(helloWorldPing, 3000);
  }
}

conn.addEventListener('message', function(e) {
  console.log(e.data);
})

</script>
```

Notice that we can added inline Javascript to our view.  This will be added to the body of the HTML output.

##### Testing

8) Now we are ready to run our Websocket server.  On the command line change the directory into the `server` project and then start the server with `./run`.

9) Now open up a browser window to point to the path `/frontend/websocket`.  Open up the web browser developer tools and view the contents of the "Console" pane.  There should be a message with the contents "Connection established!".  Now keep this window open and open a second window to the same `/frontend/websocket` path and then open the Console pane in this window as well.  Now view the first window and we should see that the second window sent it a message.  Now the two windows will communicate with each other every 3 seconds.


#### How it Works...

In our loaders config we add the following line `$config->application->pluginsDir` to the registerDirs call to allow autoloading of our security plugin.

Now we will look at our Ratchet Chat class in `app/library/Chat.php`.  During a Websocket lifecycle there will be an "open" and "close" event and many "message" events.  This class simply adds handlers for these three cases.

In open event we will first save the connection in our SplObjectStorage object.  Note that the echo "New connection..." line does not send anything to the client and instead simply sends the text to standard output which in this case is our command terminal.  Next we will send a message to each other client to notify them that a new client connected to the server.

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

Next in our "message" event handler we will simply broadcast a received message to every other client connected to the server.

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

Finally in the "close" event handler we will simply remove the client connection from the SplObjectStorage object.

    public function onClose(Ratchet\ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
    }

So far we have covered the most important part of understanding Websockets on the server.  What would quickly become obvious in a real application is that we need a way to specify specific actions to perform on each message.  Eventually one would begin to do something such as encoding the message in JSON format and to look at a field such as "type" or "action" to see which function should be performed.  So one could find that they are reinventing the wheel and this is where WAMP (Web Application Messaging Protocol) comes in.  Unfortunately Ratchet doesn't current support the latest versions of the WAMP protocol that modern Javascript Websocket libraries support and this is where the Thruway library comes in.  Thruway builds upon Ratchet and so the lessons learned here will be a good start to building a high end Phalcon Websocket server.

Now back to building our Websocket server.  We need a way to run our Chat event handler and the most direct way to accomplish this is to wrap the Chat application in several layers.  At the heart of this is an asyncronous event loop.  This is important because it means that the CPU will only spend time on the loop when there is something to be done and it will not long poll using a wait timer.  Remember back in the introduction when we discussed how having the browser long poll the web server for AJAX updates is very efficient?  Well its just as inefficient from within a server process as well.  Discussing in detail the inner workings of the event loop is beyond the scope of this recipe

Notice the "8080" argument on the HttpServer object.  This is the TCP port that Websocket server will listen on.

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

Now we are ready to look at the front part of the Websockets.  Since this is the part of the solution that is delivered to the client through the web server we must use the "frontend" scaffold.  The Javsacript used to connect is delivered through the view located at `frontend/app/views/websocket/index.volt`.

First we will connect to the server at domain "localhost" (local computer) and port 8080.

    var conn = new WebSocket('ws://localhost:8080');

The handling of events on the client and server are very similar.  For the server and client we both have an "open", "close" and "message" event.

We will listen to the "open" event on the connection.  This event will trigger when a connection is made.  We will then call the helloWorldPing function which will then send the message "Hello World!" to the server (which will then broadcast to all other clients) and we will repeat this every 3 seconds.

    conn.addEventListener('open', function(e) {
      console.log("Connection established!");
      helloWorldPing()
    });

    function helloWorldPing() {
      if (conn.readyState === conn.OPEN) {
        conn.send('Hello World!');
        setTimeout(helloWorldPing, 3000);
      }
    }

Next we will simply log incoming messages to the Javascript console.

    conn.addEventListener('message', function(e) {
      console.log(e.data);
    })



Important Reading:

* React PHP http://reactphp.org/
* Ratchet http://socketo.me/
* Thruway https://github.com/voryx/Thruway
