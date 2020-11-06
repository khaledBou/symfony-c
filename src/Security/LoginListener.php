<?php

namespace App\Security;

use App\Entity\User\ImportableUserInterface;
use App\Service\NetworkHelper;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Gestion des redirections à l'arrivée sur la page de connexion.
 */
class LoginListener
{
    /**
     * @var NetworkHelper
     */
    private $networkHelper;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @param NetworkHelper         $networkHelper
     * @param RouterInterface       $router
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(NetworkHelper $networkHelper, RouterInterface $router, TokenStorageInterface $tokenStorage)
    {
        $this->networkHelper = $networkHelper;
        $this->router = $router;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param RequestEvent $event
     */
    public function onKernelRequest(RequestEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $requestedRoute = $event->getRequest()->get('_route');

        $token = $this->tokenStorage->getToken();
        $isLoggedIn = null !== $token ? $token->getUser() instanceof ImportableUserInterface : false;

        /* Redirection vers la route 'mandatary_index'
         * lorsqu'on demande une des routes de login alors qu'on est déjà connecté. */
        if ($isLoggedIn && in_array($requestedRoute, [
            'login',
            'sso_login',
            'connect_keycloak_start',
        ])) {
            $homepageUrl = $this->router->generate('mandatary_index');
            $event->setResponse(new RedirectResponse($homepageUrl));

            return;
        }

        if ('login' === $requestedRoute) {
            $loginRoute = $this->networkHelper->getConfig('login_route');
            if ($requestedRoute !== $loginRoute) { // évite une boucle de redirection
                $loginUrl = $this->router->generate($loginRoute);
                $event->setResponse(new RedirectResponse($loginUrl));

                return;
            }
        }
    }
}
