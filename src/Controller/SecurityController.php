<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Contrôleur pour la sécurité.
 */
class SecurityController extends AbstractController
{
    /**
     * @Route("/sso/login", name="sso_login")
     *
     * @return RedirectResponse
     */
    public function ssoLogin(): RedirectResponse
    {
        $landingRoute = $this->getNetworkHelper()->getConfig('login_route');

        return $this->redirectToRoute($landingRoute);
    }

    /**
     * @Route("/login", name="login")
     *
     * @param AuthenticationUtils $authenticationUtils
     *
     * @return Response
     */
    public function formLogin(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();

        if (null !== $error) {
            $this->addFlash('error', $this->getTranslator()->trans("Identifiants invalides."));
        }

        return $this->render(sprintf('security/login.%s.html.twig', $this->getNetworkHelper()->getNetworkId()), [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $error,
        ], new Response('', Response::HTTP_UNAUTHORIZED));
    }

    /**
     * Début du processus de connexion via Keycloak.
     *
     * @Route("/connect/keycloak", name="connect_keycloak_start")
     *
     * @param ClientRegistry $clientRegistry
     *
     * @return RedirectResponse
     */
    public function keycloakLogin(ClientRegistry $clientRegistry): RedirectResponse
    {
        return $clientRegistry
            ->getClient($this->getNetworkHelper()->getConfig('keycloak_client_id'))
            ->redirect([
                'email',
            ], [])
        ;
    }

    /**
     * Redirection après connexion à Keycloak,
     * telle que paramétrée dans config/packages/knpu_oauth2_client.yaml.
     *
     * @Route("/connect/keycloak/check", name="connect_keycloak_check")
     *
     * @param Request        $request
     * @param ClientRegistry $clientRegistry
     */
    public function connectCheckAction(Request $request, ClientRegistry $clientRegistry)
    {
    }
}
