<?php

namespace App\EventListener;

use App\Service\GoogleOAuthHelper;
use App\Service\NetworkHelper;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;

/**
 * Écoute et récupère les tokens OAuth de Google API,
 * de manière la plus transparente possible.
 *
 * À chaque requête, une fois le coach connecté, l'écouteur s'assure qu'un token :
 * - peut être déduit du paramètre `code` lorsqu'il est passé dans l'URL
 * - est présent dans la session
 * Si ce n'est pas le cas, il redirige vers le processus d'authentification de Google.
 * Une fois l'utilisateur authentifié auprès de Google,
 * il est redirigé vers l'application et un paramètre `code` est passé dans l'URL.
 * L'écouteur entre de nouveau en jeu et inspecte la requête.
 * Cette fois, le token peut être déduit du paramètre `code` et va alimenter la session.
 * L'utilisateur est alors considéré comme authentifié.
 * Lorsque le token expirera, l'utilisateur sera de nouveau invité à s'authentifier auprès de Google.
 *
 * @see https://symfony.com/doc/current/event_dispatcher.html
 */
class GoogleApiTokenListener
{
    /**
     * @var GoogleOAuthHelper
     */
    private $googleOAuthHelper;

    /**
     * @var Security
     */
    private $security;

    /**
     * @var RouterInterface
     */
    private $router;


    /**
     * @var NetworkHelper
     */
    private $networkHelper;

    /**
     * GoogleApiTokenListener constructor.
     *
     * @param GoogleOAuthHelper $googleOAuthHelper
     * @param Security          $security
     * @param RouterInterface   $router
     * @param NetworkHelper     $networkHelper
     */
    public function __construct(GoogleOAuthHelper $googleOAuthHelper, Security $security, RouterInterface $router, NetworkHelper $networkHelper)
    {

        $googleOAuthHelper
            ->setClientScopes([
                // Tous les scopes qui seront utilisés par l'application
                \Google_Service_Calendar::CALENDAR,
            ])
        ;

        $this->googleOAuthHelper = $googleOAuthHelper;
        $this->security = $security;
        $this->router = $router;
        $this->networkHelper = $networkHelper;
    }

    /**
     * @see https://github.com/googleapis/google-api-php-client#authentication-with-oauth
     * @see https://developers.google.com/identity/protocols/oauth2/service-account#delegatingauthority
     * @see https://support.google.com/a/answer/162106
     *
     * @param RequestEvent $event
     *
     * @throws \Google_Exception
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        /* Neutralise l'écouteur lorsque la requête n'est pas la principale
         * ou que l'utilisateur n'est pas connecté. */
        if (!$event->isMasterRequest() || null === $this->security->getUser()) {
            return;
        }

        /**
         * Les URL de redirection autorisées doivent être paramétrées chez Google.
         * Puisqu'il n'est pas possible de paramétrer toutes les URL (car dynamiques),
         * on indique que la page de redirection après authentification chez Google
         * est la page d'accueil de l'application plutôt que vers la page en cours de consultation.
         *
         * L'appel à setRedirectUri doit être fait avant l'appel à fetchAccessTokenWithAuthCode.
         *
         * @see https://console.developers.google.com/apis/credentials
         * @see https://github.com/googleapis/google-api-php-client#authentication-with-oauth
         */
        $redirectUrl = $this->router->generate('mandatary_index', [], RouterInterface::ABSOLUTE_URL);
        // $this->googleApiHelper->getClient()->setRedirectUri($redirectUrl);

        $this->googleOAuthHelper->getClient()->setRedirectUri($redirectUrl);

        // La requête
        $request = $event->getRequest();


        if ($this->googleOAuthHelper->getClient()->isAccessTokenExpired()) {
            $clientAuthFile = sprintf(
                'config/google/credentials/client_service_account_%s.json',
                $this->networkHelper->getNetworkId()
            );
            $this->googleOAuthHelper->setClientAuthConfig($clientAuthFile);


            // Impersonate as coach for creation event
            $emailCoach = $this->security->getToken()->getUser()->getEmail();
            $this->googleOAuthHelper->getClient()->setSubject($emailCoach);

            $this->googleOAuthHelper->getClient()->useApplicationDefaultCredentials();

            $tokenOAuth = $this->googleOAuthHelper->getClient()->fetchAccessTokenWithAssertion();

            if (isset($tokenOAuth['error'])) {
                $tokenOAuth = null;
            }
        } else { // Sinon, on tente de récupérer le token depuis la session
            // @var array|null
            $tokenOAuth = $this->googleOAuthHelper->getTokenFromSession();
        }

        // Si les opérations précédentes ont permis de récupérer un token
        if (null !== $tokenOAuth) {
            $this->googleOAuthHelper->getClient()->setAccessToken($tokenOAuth);

            // On vérifie qu'il n'est pas périmé avant de le sauvegarder dans la session
            if (!$this->googleOAuthHelper->getClient()->isAccessTokenExpired()) {
                $this->googleOAuthHelper->saveTokenToSession($tokenOAuth);

                /* Récupération du state qui avait été passé à Google,
                 * afin de réafficher la page qui était en cours de consultation. */
                $state = $request->get('state', null);

                if (null !== $state) {
                    $state = json_decode($state, true);

                    $url = $this->router->generate($state['route'], $state['route_params'], RouterInterface::ABSOLUTE_URL);
                    $response = new RedirectResponse($url);
                    $event->setResponse($response);
                }

                // Réussite, nul besoin d'aller plus loin
                return;
            }
        }

        /* Si on en est là, c'est que les opérations précédentes ont échoué.
         * On redirige alors vers la page d'authentification de Google,
         * qui redirigera à son tour vers notre application en fournissant cette fois
         * le paramètre GET 'code', et les opérations ci-dessus seront rejouées. */

        /* La page en cours de consultation est passée dans
         * le state de Google pour pouvoir la réafficher
         * une fois que l'authentification de Google sera passée.
         * On indiquera de réafficher la page d'accueil dans le cas où
         * on viendrait de la page de connexion SSO. */
        $route = $request->get('_route');
        $routeParams = $request->get('_route_params');
        if ('sso_login' === $route) {
            $route = 'mandatary_index';
            $routeParams = [];
        }

        $this->googleOAuthHelper->getClient()->setState(json_encode([
            'route' => $route,
            'route_params' => $routeParams,
        ]));
    }
}
