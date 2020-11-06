<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Manipulation de l'API du CRM Immo-Reseau.
 */
class ImmoReseauApiHelper
{
    /**
     * @var string
     */
    private $apiUrl;

    /**
     * @var string
     */
    private $apiToken;

    /**
     * @param ParameterBagInterface $parameterBag
     */
    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->apiUrl = $parameterBag->get('immo_reseau_api_url');
        $this->apiToken = $parameterBag->get('immo_reseau_api_token');
    }

    /**
     * Fait un appel à l'API.
     *
     * @param string $endpoint
     * @param array  $parameters
     *
     * @return \stdClass|null
     */
    public function call($endpoint, $parameters): ?\stdClass
    {
        $url = sprintf('%s/%s?%s', $this->apiUrl, $endpoint, http_build_query($parameters));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            sprintf('Authorization:Bearer %s', $this->apiToken),
            'accept:application/ld+json',
        ]);
        $output = json_decode(curl_exec($ch));
        curl_close($ch);

        return $output;
    }
}
