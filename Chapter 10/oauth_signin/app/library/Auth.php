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
