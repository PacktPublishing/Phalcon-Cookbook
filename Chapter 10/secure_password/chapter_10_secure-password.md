
## Securing passwords with hashing

In this recipe we will create a solution for protecting passwords from data security breaches by using cryptographic one-way irreversible hashing.  Using this technique will make is so that if anyone ever is able to penetrate the security of the website to steal the users passwords the data will be useless for signing into a user's profile or for using on other websites.  While it may sound like we are resigning ourselves to defeat from the very start this is in fact not the best way to think about this issue.  We must do many things to protect our system and users and each additional step simply builds our overall protection to contain a security breach from completely compromising all aspects of the system.  Users may use the same password on multiple sites and this will allow us to protect their other accounts.  Additionally hashing the user passwords is a very simple step and without this step any security audit of any value would most certainly give a system a instant fail if it failed to do this industry standard protection.

#### Getting Ready...

This recipe uses the **Phalcon Developer Tools** which we will use to setup a project skeleton.

#### How to do it...
Follow these steps to complete this recipe…

1) We need to have an application skeleton for experimentation.  If you already have such an application, you can skip this step. Create a project skeleton using the "simple" template:

`phalcon project secure_password simple`

2) Create database `secure_password`:

```
CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(30) NOT NULL,
  `password` varchar(80) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
```

3) Add the following password setting to the application configuration at `app/config/config.php`:

```php
'application' => [
    // ...
    'password' => [
        'minLen' => 4,
        'maxLen' => 12
    ]
]
```

4) Create the model `app/models/Users.php`:

```php
<?php

class Users extends Phalcon\Mvc\Model
{
    public function beforeValidationOnCreate()
    {
        if (!$this->hashPassword()) {
            return false;
        }
    }

    public function beforeValidationOnUpdate()
    {
        if ($this->hasChanged('password')) {
            if (!$this->hashPassword()) {
                return false;
            }
        }
    }

    protected function hashPassword()
    {
        $configPassword = $this->getDI()
            ->getConfig()
            ->application->password;

        $length = mb_strlen($this->password);
        if ($length < 4 || $length > 12) {
            $this->appendMessage(new Phalcon\Mvc\Model\Message('Invalid password length', 'password', 'InvalidValue'));
            return false;
        }

        $this->password = $this->getDI()
            ->getSecurity()
            ->hash($this->password);

        return true;
    }

    public function validation()
    {
        $validator = new Phalcon\Validation();

        $validator->add('username', new Phalcon\Validation\Validator\Uniqueness([
            'message' => 'The username already exists.'
        ]));

        return $this->validate($validator);
    }

    public function initialize(){
        $this->keepSnapshots(true);
    }
}
```

5) Create controller `app/controllers/UserController.php`:

```php
<?php

class UsersController extends Phalcon\Mvc\Controller
{
    public function indexAction()
    {

    }

    public function createAction()
    {
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        $user = new Users([
            'username' => $username,
            'password' => $password
        ]);
        if(!$user->create()) {
            $this->flash->error($user->getMessages()[0]);
            return;
        }

        $this->flash->success('User created');
    }

    public function changePasswordAction()
    {
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        $user = Users::findFirstByUsername($username);
        if (!$user) {
            $this->flash->error('The user could not be found.');
            return;
        }

        $user->password = $password;
        if(!$user->save()) {
            $this->flash->error($user->getMessages()[0]);
            return;
        }

        $this->flash->success('Password Changed successfully.');
    }

    public function signinAction()
    {
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        $user = Users::findFirstByUsername($username);
        if (!$user) {
            $this->flash->error('The user could not be found.');
            return;
        }

        if (!$this->security->checkHash($password, $user->password)) {
            $this->flash->error('Invalid password.');
            return;
        }

        $this->flash->success('Signed in successfully.');
    }

}
```

6) Create Volt view `app/views/users/index.volt`:

```php
<h3>Create</h3>
<form method="post">
  <input type="text" name="username" placeholder="username"/>
  <input type="password" name="password" = placeholder="password"/>
  <input type="submit" formaction="{{ url('users/create') }}" value="Create User"/>
</form>

<h3>Signin</h3>
<form method="post">
  <input type="text" name="username" placeholder="username"/>
  <input type="password" name="password" = placeholder="password"/>
  <input type="submit" formaction="{{ url('users/signin') }}" value="Sign User"/>
</form>

<h3>Change Password</h3>
<form method="post">
  <input type="text" name="username" placeholder="username"/>
  <input type="password" name="password" = placeholder="password"/>
  <input type="submit" formaction="{{ url('users/changePassword') }}" value="Change Password"/>
</form>
```

7) In the browser go to path `/users` from the root folder.  Create a user by entering the user name and password and clicking the "Create a User" button.  Next go back to the `/users` path and sign into the system under the "Signin" section.  If that worked then go back and try to enter in the incorrect password to see that it fails.  Then go back to the `/users` path and change the users password under the "Change Password" section.  After that click back and try to sign in again with the new password.

#### How it works...

First we'll start out by looking at the Users model.

In this model we've defined two event methods "beforeValidationOnCreate" and "beforeValidationOnUpdate" that are part of the dispatch cycle and are called respectively during a model creation and on updating.

On model creation we want to always hash the user password and there is a problem with the password then we want to abort the model creation by returning false.

```php
public function beforeValidationOnCreate()
{
    if (!$this->hashPassword()) {
        return false;
    }
}
```

On model updates we only want to hash the password if the value changed from when it was taken from the database.

```php
public function beforeValidationOnUpdate()
{
    if ($this->hasChanged('password')) {
        if (!$this->hashPassword()) {
            return false;
        }
    }
}
```

However, by default our models will be incapable of knowing if their properties changed from the original database value and so we must enable property snapshots in the "initialize" event.

```php
public function initialize(){
    $this->keepSnapshots(true);
}
```

In our "hashPassword" method acts as a sort of validator that also transforms the data before storing the password field in the database.  By using this approach we ensure that the password will always be hashed each time that it is set.

He we are checking that the password is of the correct length and if it is not then we append new Message object to the model so that we can determine the reason for the model creation or update failure.  Also we must return false to halt the model dispatch cycle.

```php
$length = mb_strlen($this->password);
if ($length < 4 || $length > 12) {
    $this->appendMessage(new Phalcon\Mvc\Model\Message('Invalid password length', 'password', 'InvalidValue'));
    return false;
}
```

Finally we use the security service to hash the password.

```php
$this->password = $this->getDI()
    ->getSecurity()
    ->hash($this->password);
```

Now we'll look at the `UsersController` and since we have pushed so much of the business logic into the Users model the controller becomes fairly simple.

First we'll abbreviate the "createAction" method to remove error handling to just show the important parts.

```php
$user = new Users([
    'username' => $username,
    'password' => $password
]);
$user->create();
```

Its very simple actually.  We pass in the full password value and the model does the hashing for us.

Now lets look at an abbreviate the "changePasswordAction" method to only show the essential parts.

```php
$user = Users::findFirstByUsername($username);
$user->password = $password;
$user->save();
```

Again its very simple due to the business logic being in the model.

Now we'll finish by looking at partially abbreviated "signinAction" method

```php
$user = Users::findFirstByUsername($username);

if (!$this->security->checkHash($password, $user->password)) {
    $this->flash->error('Invalid password.');
    return;
}

$this->flash->success('Signed in successfully.');
```

The important part of this method is the "checkHash" method on the security service.  What this does is to hash the raw password from user input and to compare it with an already hashed password stored in the Users record.  Since we are able to make use of the hashed password without knowing its original value we have proven that storing the original value is only a security risk without benefits.
