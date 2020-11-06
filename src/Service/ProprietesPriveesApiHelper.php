<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Manipulation de l'API du CRM Proprietes-Privees.
 */
class ProprietesPriveesApiHelper
{
    /**
     * @var string
     */
    private $apiId;

    /**
     * @var string
     */
    private $apiAppName;

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var string
     */
    private $apiUrl;

    /**
     * @param ParameterBagInterface $parameterBag
     */
    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->apiId = $parameterBag->get('proprietes_privees_api_id');
        $this->apiAppName = $parameterBag->get('proprietes_privees_api_app_name');
        $this->apiKey = $parameterBag->get('proprietes_privees_api_key');
        $this->apiUrl = $parameterBag->get('proprietes_privees_api_url');
    }

    /**
     * Fait un appel à l'API.
     *
     * @param string $endpoint
     * @param array  $parameters
     * @param string $method
     *
     * @return \stdClass|null
     */
    public function call(string $endpoint, array $parameters, string $method = 'GET'): ?\stdClass
    {
        $queryParameters = $this->buildQueryParameters('POST' === $method ? [] : $parameters);

        $url = sprintf('%s/%s?%s', $this->apiUrl, $endpoint, http_build_query($queryParameters));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        if ('POST' === $method) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
            curl_setopt($ch, CURLOPT_POSTREDIR, CURL_REDIR_POST_ALL);
        }
        $output = json_decode(curl_exec($ch));
        curl_close($ch);

        return $output;
    }

    /**
     * Construit les paramètres de la query string.
     *
     * @param array $parameters
     *
     * @return array
     */
    private function buildQueryParameters(array $parameters): array
    {
        $params = [
            'identity' => $this->apiId,
            'timestamp' => microtime(true),
        ];
        $parameters = $parameters ? array_merge($params, $parameters): $params;

        return array_merge($parameters, [
            'signature' => $this->generateSignature($parameters, $this->apiAppName, $this->apiKey),
        ]);
    }

    /**
     * Construit une signature à partir de ces trois paramètres.
     *
     * @param array  $parameters
     * @param string $salt
     * @param string $credential
     *
     * @return string
     */
    private function generateSignature(array $parameters, string $salt, string $credential): string
    {
        $parameters = http_build_query($parameters).$salt;
        $hash = hash_hmac('sha1', $parameters, $credential, false);
        $signature = base64_encode($hash);

        return $signature;
    }
}
