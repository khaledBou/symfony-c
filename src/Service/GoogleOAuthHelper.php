<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Manipulation de l'API Google.
 *
 * @see https://developers.google.com/identity/protocols/oauth2/web-server
 */
class GoogleOAuthHelper
{
    /**
     * Nom de la variable de session contenant le token.
     *
     * @var string
     */
    const SESSION_TOKEN_NAME = 'google_oauth_token';

    /**
     * @var string
     */
    private $kernelProjectDir;

    /**
     * @var \Google_Client
     */
    private $client;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @param ParameterBagInterface $parameterBag
     * @param SessionInterface      $session
     */
    public function __construct(ParameterBagInterface $parameterBag, SessionInterface $session)
    {
        $this->kernelProjectDir = $parameterBag->get('kernel.project_dir');
        $this->client = new \Google_Client();
        $this->session = $session;
    }

    /**
     * Définit les scopes à utiliser.
     *
     * @param array $scopes
     *
     * @return self
     */
    public function setClientScopes(array $scopes): self
    {
        $this->client->setScopes($scopes);

        return $this;
    }

    /**
     * Définit le nom de fichier à utiliser,
     * dans le répertoire config/google/credentials.
     *
     * @see https://developers.google.com/identity/protocols/oauth2/web-server?csw=1#php
     *
     * @param string $file
     *
     * @return GoogleOAuthHelper
     *
     * @throws \Google_Exception
     */
    public function setClientAuthConfig(string $file): self
    {

        $this->client->setAuthConfig(sprintf(
            '%s/%s',
            $this->kernelProjectDir,
            $file
        ));

        return $this;
    }

    /**
     * @return \Google_Client
     */
    public function getClient(): \Google_Client
    {
        return $this->client;
    }

    /**
     * @return array|null
     */
    public function getTokenFromSession(): ?array
    {
        return $this->session->get(self::SESSION_TOKEN_NAME, null);
    }

    /**
     * @param array $token
     *
     * @return void
     */
    public function saveTokenToSession(array $token): void
    {
        $this->session->set(self::SESSION_TOKEN_NAME, $token);
    }
}
