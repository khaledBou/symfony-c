<?php

namespace App\Service;

use App\Entity\User\Coach;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Manipulation des réseaux (Proprietes-Privees, Immo-Reseau, …).
 */
class NetworkHelper
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var array
     */
    private $config;

    /**
     * @param RequestStack          $requestStack
     * @param ParameterBagInterface $parameterBag
     */
    public function __construct(RequestStack $requestStack, ParameterBagInterface $parameterBag)
    {
        $this->request = $requestStack->getCurrentRequest();
        $this->config = $parameterBag->get('app');
    }

    /**
     * Détermine le réseau en fonction du domaine de la requête HTTP.
     *
     * @return string|null
     *
     * @throws \Exception                    S'il n'y a pas de requête HTTP (appel depuis la console Symfony par exemple)
     * @throws InvalidConfigurationException Lorsque le domaine courant n'est pas paramétré
     */
    public function getNetworkId(): ?string
    {
        $networkId = null;

        if (null !== $this->request) {
            $domain = $this->request->getHost();

            foreach ($this->config['networks'] as $key => $network) {
                if ($domain === $network['domain']) {
                    $networkId = $key;
                    break;
                }
            }

            if (null === $networkId) {
                throw new InvalidConfigurationException(sprintf(
                    'Domain "%s" was not found in "app.networks" configuration.',
                    $domain
                ));
            }
        }

        return $networkId;
    }

    /**
     * Indique si le réseau courant est celui de l'utilisateur.
     *
     * @param Coach $user
     *
     * @return bool
     */
    public function isUserAuthorizedOnCurrentNetwork(Coach $user): bool
    {
        $networkId = $this->getNetworkId();

        return null !== $networkId ? $user->getNetwork() === $networkId : false;
    }

    /**
     * Indique si un réseau est celui de l'utilisateur.
     *
     * @param Coach  $user
     * @param string $networkId
     *
     * @return bool
     */
    public function isUserAuthorizedOnNetwork(Coach $user, string $networkId): bool
    {
        return $user->getNetwork() === $networkId;
    }

    /**
     * Récupère la valeur d'un paramètre de la configuration du réseau.
     *
     * @param string $key Paramètre dont récupérer la valeur
     *
     * @return mixed
     */
    public function getConfig(string $key)
    {
        $config = null;

        $networkId = $this->getNetworkId();

        if (null !== $networkId) {
            if (!isset($this->config['networks'][$networkId][$key])) {
                throw new \Exception(sprintf(
                    'Configuration key "%s" was not found in "app.networks.%s" configuration.',
                    $key,
                    $networkId
                ));
            }

            $config = $this->config['networks'][$networkId][$key];
        }

        return $config;
    }

    /**
     * Récupère le nom du réseau.
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        $networkId = $this->getNetworkId();

        return null !== $networkId ? $this->config['networks'][$networkId]['name'] : null;
    }

    /**
     * Récupère l'URL de redirection après déconnexion.
     *
     * @return string|null
     */
    public function getRedirectUrlAfterLogout(): ?string
    {
        $networkId = $this->getNetworkId();

        return null !== $networkId ? $this->config['networks'][$networkId]['redirect_url_after_logout'] : null;
    }
}
