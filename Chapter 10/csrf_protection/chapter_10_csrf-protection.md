
## Preventing Cross-Site Request Forgery (CSRF) attacks

Cross-Site Request Forgery attacks are a very old class of vulnerability that must be addressed for proper security.  The issue arrises when first website uses Javascript to make a request to a second website and the second website is unable to tell if the user made that request themselves through an action on its own website itself or through another website.  If there is no protection for this fraudulent request then a second website can perform actions such as attempting to sign in for the user or if the user is already signed in then requesting API data that could be sensitive.  In this recipe we will detail some methods for securing this security vulnerability.

#### Getting Ready...

This recipe uses the **Phalcon Developer Tools** which we will use to setup a project skeleton.

#### How to do it...
Follow these steps to complete this recipe…

1) We need to have an application skeleton for experimentation.  If you already have such an application, you can skip this step. Create a project skeleton using the "simple" template:

`phalcon project csrf_protection simple`

2) Create controller `app/controllers/ControllerBase.php` to be used as the base controller for all other controllers:

```php
<?php

use Phalcon\Mvc\Controller;

abstract class ControllerBase extends Controller
{
    public function beforeExecuteRoute($dispatcher)
    {
        $this->response->setHeader('X-Frame-Options', 'SAMEORIGIN');
    }
}
```

3) Create controller `app/controllers/SigninController.php`

```php
<?php

class SigninController extends ControllerBase
{
    public function indexAction()
    {
        if ($this->request->isPost()) {
            if ($this->security->checkToken()) {
                $this->flash->success('Succesfully signed in.');
            } else {
                $this->flash->error('CSRF token did not match.');
            }
        }
    }
}
```

4) Create view `app/views/signin/index.volt`:

```
<h3>CSRF example</h3>

<div>
  <form method="post">
    <input type="text" name="email" placeholder="email"   value=""/>
    <input type="hidden" name="{{ security.getTokenKey() }}" value="{{ security.getToken() }}"/>
    <input type="submit" formaction="{{ url('signin') }}" value="Sign In"/>
  </form>
</div>

<div>
  {{content()}}
</div>
```

5) In the browser go to the root of the project path and open up two identical windows to the path `/signin`.  Now go back to the first window and click "Sign In".  Its not necessary to fill in the email text box as that is only used to fill out the page.  It should say "CSRF token did not match.".  Now immediately click the "Sign In" button again and it should say "Succesfully signed in.".

#### How it works...

When we load the `signin/index.volt` view it makes a call to `security.getTokenKey()` and `security.getToken()` which then store these values in the PHP session as `$PHALCON/CSRF/KEY$` and `$PHALCON/CSRF$`.  Each time that the signin page is accessed it generates new session values.

If we look in the sign in controller then we see that it calls the `checkToken` method on the security service.  This checks to make sure that the security token key and value that were provided in the signin form match the values stored in the session variables.

```php
if ($this->security->checkToken()) {
    $this->flash->success('Succesfully signed in.');
} else {
    $this->flash->error('CSRF token did not match.');
}
```

Additionally as another form of protection we are making each response send back a HTTP header that tells the browser that our page should never be embedded within another page's iframe as this could open up additional security vulnerabilities.

```php
public function beforeExecuteRoute($dispatcher)
{
    $this->response->setHeader('X-Frame-Options', 'SAMEORIGIN');
}
```

In this recipe we are using an event in our base controller but this could also be performed by attaching a plugin to the dispatcher that listens for this same event.  There is often more than one way to do things in Phalcon.  Examples of how to setup a dispatcher plugin can be found in many other recipes in this book.
