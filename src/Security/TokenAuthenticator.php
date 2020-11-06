<?php

namespace App\Security;

use App\Service\ProprietesPriveesApiHelper;
use App\Service\NetworkHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

/**
 * Authenticator par token (SSO).
 */
class TokenAuthenticator extends AbstractGuardAuthenticator
{
    /**
     * @var ProprietesPriveesApiHelper
     */
    private $proprietesPriveesApiHelper;

    /**
     * @var NetworkHelper
     */
    private $networkHelper;

    /**
     * @param ProprietesPriveesApiHelper $proprietesPriveesApiHelper
     * @param NetworkHelper              $networkHelper
     */
    public function __construct(ProprietesPriveesApiHelper $proprietesPriveesApiHelper, NetworkHelper $networkHelper)
    {
        $this->proprietesPriveesApiHelper = $proprietesPriveesApiHelper;
        $this->networkHelper = $networkHelper;
    }

    /**
     * @param Request $request
     *
     * @return bool
     */
    public function supports(Request $request): bool
    {
        return true;
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    public function getCredentials(Request $request): array
    {
        $email = $request->query->get('email');
        $code = $request->query->get('code');
        $token = $request->query->get('token');

        if (!$email || !$code || !$token) {
            $email = null;
            $code = null;
            $token = null;
        }

        return [
            'email' => $email,
            'code' => $code,
            'token' => $token,
        ];
    }

    /**
     * @param array                 $credentials
     * @param UserProviderInterface $userProvider
     *
     * @return UserInterface|void|null
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $email = $credentials['email'];
        $code = $credentials['code'];
        $apiKey = $credentials['token'];

        if (null === $apiKey || null === $code || null === $email) {
            return;
        }

        return $userProvider->loadUserByUsername($email);
    }

    /**
     * @param array         $credentials
     * @param UserInterface $user
     *
     * @return bool
     */
    public function checkCredentials($credentials, UserInterface $user): bool
    {
        $output = $this->proprietesPriveesApiHelper->call('auth/coaching', [
            'code' => $credentials['code'],
            'token' => $credentials['token'],
        ]);

        return $output && $output->success && $output->data && $this->networkHelper->isUserAuthorizedOnCurrentNetwork($user) && $user->isEnabled();
    }

    /**
     * @param Request                 $request
     * @param AuthenticationException $exception
     *
     * @return JsonResponse
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): JsonResponse
    {
        $data = [
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData()),
        ];

        return new JsonResponse($data, Response::HTTP_FORBIDDEN);
    }

    /**
     * @param Request        $request
     * @param TokenInterface $token
     * @param string         $providerKey
     *
     * @return Response|null
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey): ?Response
    {
        return null;
    }

    /**
     * @param Request                      $request
     * @param AuthenticationException|null $authException
     *
     * @return JsonResponse
     */
    public function start(Request $request, AuthenticationException $authException = null): JsonResponse
    {
        $data = [
            'message' => 'Authentication required.',
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @return bool
     */
    public function supportsRememberMe(): bool
    {
        return false;
    }
}
