
## Using OAuth for account authorization

OAuth is a powerful and easy to use authentication system that allows a user to sign into a website through the credentials of a third party site.  This reduces the burden of needing to remember passwords and it can be more convenient as well by allowing for a single click.  In this recipe we will create a system that allows automatic account creation and sign in through a user's Gooogle account.

#### Getting Ready...

This recipe uses the **Phalcon Developer Tools** for creating project skeleton and Composer for installing third party libraries.  Additionally since this recipe uses Google as the authentication service we will need to create and configure a Google account for use with the OAuth.

This recipe has a very sensitive setup due to the conditions of the Google OAuth API.

#### How to do it...
Follow these steps to complete this recipe…

1) We need to have an application skeleton for experimentation.  If we already have such an application, we can skip this step. Create a project skeleton using the "simple" template:

`phalcon project oauth_signin simple`.

2) Use Composer to install third party packages for OAuth authentication.  Run these commands run the base path of the project folder:

```
composer require league/oauth2-client
composer require league/oauth2-google
```

3) Create `oauth_signin` database:

```
CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
```

4) Create model `app/models/Users.php`:

```php
<?php

class Users extends Phalcon\Mvc\Model {}
```

5) We need to make a Google OAuth credential.  Unfortunately even though we are only working locally the host name that points to the web server will need to be a valid domain name (not localhost) for it to be accepted by the Google OAuth API setup.  Fortunately it is not necessary to actually purchase a domain name for local use because we can instead fool the computer by using the operating systems hosts file.  The first thing is to pick a domain name for use with the project and we will refer to this as DOMAIN.com so that anywhere we see this we will instead substitute it for chosen domain name.

We will need to modify the hosts file of the operating system.  On Windows this file is located at `C:\Windows\System32\drivers\etc` and on OSX and Linux it is located at `/etc/hosts`.  While using administrator permissions modify the hosts file by adding the following:

```
127.0.0.1  DOMAIN.com
```

6) Go to `https://console.developers.google.com` and do the following:

* Create a project called "Phalcon Book".
* Click the Library tab:
  * Search for "Google+ API" and click the "ENABLE" button.
* Click the "Credentials" tab:
  * Go to the "OAuth consent screen" sub-tab, enter the required fields and click "Save".
  * Go to the "Credentials" sub-tab and click "Create credentials" -> "OAuth client ID" -> "Web Application" and then click "Create".  In the "Authorized JavaScript origins" section add "http://DOMAIN.com" and in the "Authorized redirect URIs" section add http://DOMAIN.com/session/oauth/google/" and click save.
  * After creating the credential the "Client ID" and "Client Secret" should appear.  Keep this window open because we will need both of these values for the next step.

7) Add the following "services" key to the config file located at `app/config/config.php` while filling in the "clientId" and "clientSecret" with the values from the least step:

```php
'services' => [
    'google' => [
        'clientId'     => '',
        'clientSecret' => ''
    ]
],
```

8) Create the "auth" and "oauthProviderGoogle" services in the `app/config/services.php` file:

```php
$di->setShared('auth', function() {
    $auth = new Auth();
    $auth->setDI($this);
    return $auth;
});

$di->setShared('oauthProviderGoogle', function() {
    $configProvider = $this->getConfig()
        ->services->google;

    $relativePath = $this->getUrl()
        ->get('session/oauth/google/');

    $redirectUri = 'http://' . $_SERVER['HTTP_HOST'] . $relativePath;
    $hostedDomain = 'http://' . $_SERVER['HTTP_HOST'];

    return new League\OAuth2\Client\Provider\Google([
        'clientId'     => $configProvider->clientId,
        'clientSecret' => $configProvider->clientSecret,
        'redirectUri'  => $redirectUri,
        'hostedDomain' => $hostedDomain
    ]);
});
```

9) Create controller `app/controllers/SessionController.php`:

```php
<?php

class SessionController extends Phalcon\Mvc\Controller
{
    public function indexAction()
    {
    }

    public function oauthRedirectAction()
    {
        $provider = $this->dispatcher->getParam(0);
        if (!isset($provider)) {
            error_log('The provider name is not set.');
            return false;
        }

        $authUrl = $this->getDI()
            ->getAuth()
            ->getAuthorizationUrl($provider);

        $this->response->redirect($authUrl, true);
        $this->response->send();
        return false;

    }

    public function oauthAction()
    {
        $provider = $this->dispatcher->getParam(0);
        $code = $this->request->get('code');
        if (empty($code)) {
            $this->flash->error('The OAuth provider information is invalid.');
            return false;
        }

        $ownerDetails = $this->auth->checkOauth($provider, $code);

        $email = $ownerDetails->getEmail();

        $user = Users::findFirstByEmail($email);
        if ($user) {
            $this->flash->success("User with email '$email' signed in successfully.");
        } else {
            $user = new Users();
            $user->email = $email;
            $user->save();
            $this->flash->success("Successfully created user with email: '$email'.");
        }
    }
}
```

10) Create `app/library/Auth.php`:

<?php

class Auth extends Phalcon\DI\Injectable
{
    public function checkOauth($providerName, $code)
    {
        switch ($providerName) {
            case 'google':
                $provider = $this->getDI()
                    ->getOauthProviderGoogle();
                break;
            default:
                throw new AuthException('Invalid oauth provider');
                break;
        }

        $token = $provider->getAccessToken('authorization_code', [
            'code' => $code
        ]);

        // We got an access token, let's now get the owner details
        return $provider->getResourceOwner($token);
    }

    public function getAuthorizationUrl($providerName)
    {
        switch ($providerName) {
            case 'google':
                $authUrl = $this->getDI()
                    ->getOauthProviderGoogle()
                    ->getAuthorizationUrl();
                break;
            default:
                throw new AuthException('Invalid oauth provider.');
                break;
        }

        return $authUrl;
    }
}

11) Create view `app/views/session/index.volt`:

```
<a href="{{ url('session/oauthRedirect/google') }}">Signin with Google</a>
```

12) Go to the following pages in the root path of the project:

* Go to page `/session` and click "Signin with Google".  Then proceed to sign in normally with Google and it should redirect us back to our website with the message "Successfully created user with email: 'YOUR_EMAIL@gmail.com'".
* Go to page `/session` and click "Signin with Google" and it should immediately redirect us back to website with the message "User with email 'YOUR_EMAIL@gmail.com' signed in successfully.".

#### How it works...

OAuth works using exchanges of cryptographic tokens.  When the user first clicks the "session/oauthRedirect/google" link the dispatcher sends execution to the `SessionController::oauthRedirectAction` method where we first obtain the authorization URL through the `auth` service:

```php
$authUrl = $this->getDI()
    ->getAuth()
    ->getAuthorizationUrl($provider);
```

If we then look into the `Auth::getAuthorizationUrl` method we find that we are obtaining the authorization URL from our Google OAuth provider service.

```php
$authUrl = $this->getDI()
    ->getOauthProviderGoogle()
    ->getAuthorizationUrl();
```

When we look in the `oauthProviderGoogle` service we see that we are calling out to a third party `League` package which we installed earlier using Composer.  This OAuth provider class accepts our Client ID and Client Secret which it will use to authenticate with the Google servers.  Additionally we need to supply a `redirectUri` and `hostedDomain` that must match the ones that we provided Google.  This helps to seal up any edge case mischief that a hacker could use to infiltrate a system.

```php
return new League\OAuth2\Client\Provider\Google([
    'clientId'     => $configProvider->clientId,
    'clientSecret' => $configProvider->clientSecret,
    'redirectUri'  => $redirectUri,
    'hostedDomain' => $hostedDomain
]);
```

Now that we have access to our OAuth provider service we will go back to where this started in `SessionController::oauthRedirectAction` where we finally send a HTTP header redirect to the user to send them over to the Google servers for authentication.  Note that the authorization url contains secure tokens that were just obtained through communication from our server and Google's server.

```php
$this->response->redirect($authUrl, true);
$this->response->send();
return false;
```

Now at this point we have temporarily lost control of the web request and it is now up to Google to do its part and to send the user back to the URI that we specified.  So our web server waits for the user to authenticate and when Google sends the user back the router should send the user to the `SessionController::oauthAction` action.  Next we use our `auth` service to check the "code" that Google sent pack in a query variable.

```php
$provider = $this->dispatcher->getParam(0);
$code = $this->request->get('code');

$ownerDetails = $this->auth->checkOauth($provider, $code);
```

So now lets jump into the `Auth::checkOauth` method to see what happens there.  First we use our `oauthProviderGoogle` to once again reach out to Google to see if the "code" that the user's browser just supplied agrees with Google's assessment of the situation.

```php
$token = $provider->getAccessToken('authorization_code', [
    'code' => $code
]);

After Google has agreed about the situation we would like to get further details about the user and so we reach out to Google once again to get additional data and this is why we needed to enable the Google+ API.  So then we return an owner details object.

// We got an access token, let's now get the owner details
return $provider->getResourceOwner($token);
```

Now back to our SessionController::oauthAction method we access the email address and if a user already exists with this address then we will sign them in and if not then we will create a new user.

```php
$email = $ownerDetails->getEmail();

$user = Users::findFirstByEmail($email);
if ($user) {
    $this->flash->success("User with email '$email' signed in successfully.");
} else {
    $user = new Users();
    $user->email = $email;
    $user->save();
    $this->flash->success("Successfully created user with email: '$email'.");
}
```

Note that there are many ways to use OAuth and each provider can either the most generic of configurations or in the case of Google they can have their own specific configurations and additional features.
