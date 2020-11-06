<?php

namespace App\Security;

use App\Entity\User\Coach;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Security\Authenticator\SocialAuthenticator;
use KnpU\OAuth2ClientBundle\Client\Provider\KeycloakClient;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use App\Service\NetworkHelper;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Authenticator par Keycloak.
 */
class KeycloakAuthenticator extends SocialAuthenticator
{
    /**
     * @var ClientRegistry
     */
    private $clientRegistry;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var NetworkHelper
     */
    private $networkHelper;

    /**
     * @param ClientRegistry         $clientRegistry
     * @param EntityManagerInterface $em
     * @param RouterInterface        $router
     * @param NetworHelper           $networkHelper
     */
    public function __construct(ClientRegistry $clientRegistry, EntityManagerInterface $em, RouterInterface $router, NetworkHelper $networkHelper)
    {
        $this->clientRegistry = $clientRegistry;
        $this->em = $em;
        $this->router = $router;
        $this->networkHelper = $networkHelper;
    }

    /**
     * @param Request $request
     *
     * @return bool
     */
    public function supports(Request $request): bool
    {
        return $request->attributes->get('_route') === 'connect_keycloak_check';
    }

    /**
     * @param Request $request
     *
     * @return \League\OAuth2\Client\Token\AccessToken
     */
    public function getCredentials(Request $request)
    {
        return $this->fetchAccessToken($this->getKeycloakClient());
    }

    /**
     * @param \League\OAuth2\Client\Token\AccessToken $credentials
     * @param UserProviderInterface                   $userProvider
     *
     * @return Coach $user
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        // @var Stevenmaguire\OAuth2\Client\Provider\KeycloakResourceOwner|\League\OAuth2\Client\Provider\ResourceOwnerInterface
        $keycloakUser = $this->getKeycloakClient()->fetchUserFromToken($credentials);
        $keycloakUserId = $keycloakUser->getId();
        $keycloakUserEmail = $keycloakUser->getEmail();

        /* Tente de récupérer un négociateur par son identifiant Keycloak,
         * au cas où il se serait déjà connecté. */
        $user = $this
            ->em
            ->getRepository(Coach::class)
            ->findOneByKeycloakId($keycloakUserId)
        ;

        if (null === $user) {
            // En fallback, on tente de récupérer un négociateur par son adresse mail
            $user = $this
                ->em
                ->getRepository(Coach::class)
                ->findOneByEmail($keycloakUserEmail)
            ;

            if (null !== $user) {
                // Sauvegarde l'identifiant Keycloak
                $user->setKeycloakId($keycloakUserId);
                $this->em->persist($user);
                $this->em->flush();
            } else {
                /* Tente de récupérer un employé par son identifiant Keycloak,
                 * au cas où il se serait déjà connecté. */
                $user = $this
                    ->em
                    ->getRepository(Coach::class)
                    ->findOneByKeycloakId($keycloakUserId)
                ;

                if (null === $user) {
                    // En fallback, on tente de récupérer un employé par son adresse mail
                    $user = $this
                        ->em
                        ->getRepository(Coach::class)
                        ->findOneByEmail($keycloakUserEmail)
                    ;

                    if (null !== $user) {
                        // Sauvegarde l'identifiant Keycloak
                        $user->setKeycloakId($keycloakUserId);
                        $this->em->persist($user);
                        $this->em->flush();
                    }
                }
            }
        }

        return $user;
    }

    /**
     * @param mixed         $credentials
     * @param UserInterface $user
     *
     * @return bool
     */
    public function checkCredentials($credentials, UserInterface $user): bool
    {
        return $this->networkHelper->isUserAuthorizedOnCurrentNetwork($user) && $user->isEnabled();
    }

    /**
     * @param Request        $request
     * @param TokenInterface $token
     * @param string         $providerKey
     *
     * @return RedirectResponse
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey): RedirectResponse
    {
        return new RedirectResponse($this->router->generate('mandatary_index'));
    }

    /**
     * @param Request                 $request
     * @param AuthenticationException $exception
     *
     * @return Response
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        return new Response($message, Response::HTTP_FORBIDDEN);
    }

    /**
     * @param Request                 $request
     * @param AuthenticationException $authException
     *
     * @return RedirectResponse
     */
    public function start(Request $request, AuthenticationException $authException = null): RedirectResponse
    {
        return new RedirectResponse('/connect/', Response::HTTP_TEMPORARY_REDIRECT);
    }

    /**
     * @return KeycloakClient
     */
    private function getKeycloakClient()
    {
        $keycloakClientId = $this->networkHelper->getConfig('keycloak_client_id');

        return $this->clientRegistry->getClient($keycloakClientId);
    }
}
