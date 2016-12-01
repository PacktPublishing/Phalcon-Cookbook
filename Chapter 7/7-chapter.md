7
Debugging and Profiling

In this chapter, we will cover:
* Improved exception reports with Phalcon Debug component
* Sending server log messages to the browser console
* Creating a route test suite
* Dealing with abnormal terminations in your requests
* Detecting slow queries in your application
* -- SQL queries debugging

Introduction:

Phalcon has powerful debugging and profiling tools to help ensure that your application performs correctly and efficiently.  The easiest place to start is to setup the Phalcon Debug component.  This will provide additional information within a pleasantly themed interface detailing the location and circumstances of the error.  It is also possible to log messages directly to the Firefox Javascript console using the FirePHP logger.  This can save a lot of time by not needing to switch between a system shell window and the browser and it will present a nicer interface for inspecting the contents of PHP arrays.  Next we setup a test suite for routing to ensure that each route can reach its final destination.  We will then create a plugin for handling abnormal termination in our application dispatch cycle and then another plugin for detecting slow queries in our application.

...SQL queries debugging
