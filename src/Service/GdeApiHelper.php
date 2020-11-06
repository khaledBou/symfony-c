<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Manipulation de l'API GDE (Gestion Des Événements).
 */
class GdeApiHelper
{
    /**
     * @var string
     */
    private $apiUrl;

    /**
     * @param ParameterBagInterface $parameterBag
     */
    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->apiUrl = $parameterBag->get('gde_api_url');
        $this->apiUser = $parameterBag->get('gde_api_user');
        $this->apiPassword = $parameterBag->get('gde_api_password');
    }

    /**
     * Fait un appel à l'API.
     *
     * @param string $endpoint
     * @param array  $parameters
     *
     * @return array|\stdClass|null
     */
    public function call($endpoint, $parameters)
    {
        $url = sprintf('%s/%s?%s', $this->apiUrl, $endpoint, http_build_query($parameters));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERPWD, sprintf('%s:%s', $this->apiUser, $this->apiPassword));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        $output = json_decode(curl_exec($ch));
        curl_close($ch);

        return $output;
    }
}
