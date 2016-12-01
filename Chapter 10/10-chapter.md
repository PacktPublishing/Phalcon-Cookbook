10
Securing your applications

In this chapter, we will cover:
    Securing data by encrypting it
    Securing passwords with hashing
    Preventing Cross Site Scripting (XSS) attacks
    Preventing Cross-Site Request Forgery (CSRF) attacks
    Implementing an Alternative Access Control Lists
    Using OAuth for account authorization

Introduction:

Phalcon offers a wealth of built in classes and interfaces for hardening your application.  These can be used and extended to fit many scenarios but sometimes we will need a custom solution.  The Phalcon Incubator can help with this as it provides alternative classes and different ideas on what is possible.  We will implement the Database ACL class from the Incubator to keep our ACL data stored within the database.

Next we will implement password hashing with the built-in Phalcon\Security.  This will allow us to use a very simple interface for protected passwords that was designed to evolve to meet our future security needs.

There are a wide range of applications that are based upon the displaying of user generated data and without a strict focus on security this will open up trivial Cross Site Scripting (XSS) security vulnerabilities in an application.  Fortunately the solution for this serious problem is rather simple and so we will explore a simple and elegant solution for preventing XSS attacks by using Phalcon\Security.

The next type of attack occurs when the server is accepting user input.  The problem arises when another website (running in the user's browser) makes a request to our application.  Since this request is coming from the user's browser it appears as though the user initiated the action from without our system and so malicious signins and comment postings can be made against our user's wishes.  The solution for this Cross-Site Request Forgery (CSRF) attack is to use a nonce token in all of our forms.  This ensures that the request came from within our site and not from outside of it.  We will explore a solution for this by using Phacon\Security.
