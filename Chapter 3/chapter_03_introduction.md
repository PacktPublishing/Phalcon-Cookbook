# 3

## Processing Requests

In this chapter, we will cover:
* 3 - Understanding the default routing strategy for controllers
* 3 - Handling a request along multiple controllers
* 3 - Using flexible key-value action parameter pairs
* 6 - Restricting controllers by managing end user privileges
* 5 - Making unsuccessful requests fail softly
* 6 - Creating a server-sent message server
* 5 - Creating a Ratchet Websocket server

Introduction:
An essential part of any modern framework is processing requests from the web browser and directing it towards the appropriate server side controller logic.  This routing strategy can be use for generic common case scenarios as well as complicated custom ones.  A common setup usually involves creating a default routing pattern that will handle the bulk of requests and then defining additional specific routes to handle unique cases.  By following this pattern we can quickly setup an application and then begin to customize it one part at a time.  Then sometimes while processing a request it becomes apparent that it is necessary to have the logic flow among a progression of controllers as certain exceptional cases are discovered.  This feature allows for a clean and smooth logic flow while eliminating additional code that would cause your code to violate the DRY (Don't Repeat Yourself) programming principle.  Then we will show you how to use key-value parameters in your URL to pass data to your controllers.  Doing this allows flexibility in your routing while still using the default routing strategy.  However, all of this power would be detrimental to your system if you are unable to restrict administrative access to only a certain class of users or to prevent unauthenticated users from viewing private data.  So we will show you how to manage end user privileges to create classes of users that can access only their prescribed sections of your web application.  So at this point your application is secure and you have a variety of routing strategies to choose from but even then sometimes your application logic will hit a critical error or perhaps the user just entered in an incorrect URL.  In this case we will be wanting to show a nice looking error screen to let the user know that something didn't quite work as expected.  This is all good but the web is evolving at a tremendous pace and for some types of applications the traditional model of browser to server and then back to the browser request is just not enough.  Sometimes you will need to have the web server need to be able to push data to the browser in an efficient manner and the current method of long polling (requesting data every few seconds) will not scale out to large amounts of users.  We will show you how to create a persistent connection to the server so that the server can push data to the browser using the server-sent message API.  However, while simple and prefect for many use cases this would not be appropriate for example; a chat application or a game.  This is where websockets come in.  By implementing a websocket server you will be able to create a long-lived bidirectional communication system between the browser and server.

... All of the examples in this chapter will use the multi-module scaffold fro the Phalcon Develoer Tools
