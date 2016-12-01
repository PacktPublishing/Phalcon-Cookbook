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
