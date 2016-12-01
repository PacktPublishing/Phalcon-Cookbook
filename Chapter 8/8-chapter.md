8
Fine Tuning and Optimizing

In this chapter, we will cover:
* Handling a database timeout with a reconnect plugin
* Precompiling Volt templates for increased performance and security
* Optimizing PHQL queries
* Caching Model Meta data and Query Results for faster performance

Introduction:

In this chapter we will cover some advanced techniques for improving the reliability and scaling of our Phalcon application.  We will create a database timeout plugin that will ensure that our long running processes continue are able to continue functioning even after exceeding the database connection timeout duration.  This will allow us to use Phalcon for long running CLI scripts.  Then we will create a multi-module system that uses the CLI environment to first precompile our Volt templates for increasing performance in our web environment as well as increasing security by making the cache directory read-only for the web server.  We will then optimize our PHQL queries by demonstrating three successively faster query implementations and the specific differences between them.  Finally we will use the APC extension to cache models meta data and specific query results to persist beyond the execution of a single process and dramatically speeding up our application in the process.
