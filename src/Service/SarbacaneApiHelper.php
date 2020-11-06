<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Manipulation de l'API Sarbacane.
 */
class SarbacaneApiHelper
{
    /**
     * @var string
     */
    private $apiUrl;

    /**
     * @var string
     */
    private $accountId;

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @param ParameterBagInterface $parameterBag
     */
    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->apiUrl = $parameterBag->get('sarbacane_api_url');
        $this->accountId = $parameterBag->get('sarbacane_account_id');
        $this->apiKey = $parameterBag->get('sarbacane_api_key');
    }

    /**
     * Fait un appel à l'API.
     *
     * @param string $endpoint
     * @param array  $parameters
     *
     * @return \stdClass|array|null
     */
    public function call($endpoint, $parameters)
    {
        $url = sprintf('%s/%s', $this->apiUrl, $endpoint);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERPWD, sprintf('%s:%s', $this->accountId, $this->apiKey));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($parameters));
        $output = json_decode(curl_exec($ch));
        curl_close($ch);

        return $output;
    }
}
