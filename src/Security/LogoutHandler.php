<?php

namespace App\Security;

use App\Service\NetworkHelper;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Logout\DefaultLogoutSuccessHandler;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;

/**
 * Gestion des redirections après déconnexion.
 */
class LogoutHandler extends DefaultLogoutSuccessHandler implements LogoutSuccessHandlerInterface
{
    /**
     * @var NetworkHelper
     */
    private $networkHelper;

    /**
     * Injection de dépendance.
     *
     * @param NetworkHelper $networkHelper
     *
     * @return void
     */
    public function setNetworkHelper(NetworkHelper $networkHelper): void
    {
        $this->networkHelper = $networkHelper;
    }

    /**
     * Injection de dépendance.
     *
     * @param ClientRegistry $clientRegistry
     */
    public function setClientRegistry(ClientRegistry $clientRegistry)
    {
        $this->clientRegistry = $clientRegistry;
    }

    /**
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function onLogoutSuccess(Request $request): RedirectResponse
    {
        /**
         * À ce stade, on est déjà déconnecté de Symfony.
         *
         * On va en plus déconnecter l'utilisateur de Keycloak,
         * qu'on se soit connecté avec Keycloak ou non,
         * avant de le renvoyer vers l'URL 'redirect_url_after_logout' paramétrée dans app.yaml.
         */
        $baseAuthorizationUrl = $this
            ->clientRegistry
            ->getClient($this->networkHelper->getConfig('keycloak_client_id'))
            ->getOAuth2Provider()
            ->getBaseAuthorizationUrl()
        ;

        $url = sprintf(
            '%s?redirect_uri=%s',
            str_replace('/protocol/openid-connect/auth', '/protocol/openid-connect/logout', $baseAuthorizationUrl),
            $this->networkHelper->getRedirectUrlAfterLogout()
        );

        return new RedirectResponse($url);
    }
}
