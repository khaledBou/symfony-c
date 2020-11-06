<?php

namespace App\Twig;

use App\Service\NetworkHelper;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFunction;
use Twig\TwigFilter;

/**
 * Extension Twig.
 */
class AppExtension extends AbstractExtension implements GlobalsInterface
{
    /**
     * L'identifiant du réseau correspondant au domaine en cours de consultation.
     *
     * @var string|null
     */
    private $networkId = null;

    /**
     * Le nom de l'application.
     *
     * @var string|null
     */
    private $appName = null;

    /**
     * L'identifiant Google Analytics.
     *
     * @var string|null
     */
    private $googleAnalyticsId = null;

    /**
     * URL de Freshdesk.
     *
     * @var string|null
     */
    private $freshdeskUrl = null;

    /**
     * @param ParameterBagInterface $parameterBag
     * @param NetworkHelper         $networkHelper
     */
    public function __construct(ParameterBagInterface $parameterBag, NetworkHelper $networkHelper)
    {
        $networkId = $networkHelper->getNetworkId();

        if ($networkId) {
            $this->networkId = $networkId;
            $this->appName = $parameterBag->get(sprintf('app.networks.%s.name', $networkId));
            $this->googleAnalyticsId = $parameterBag->get(sprintf('app.networks.%s.google_analytics_id', $networkId));
            $this->freshdeskUrl = $parameterBag->get(sprintf('app.networks.%s.freshdesk_url', $networkId));
        }
    }

    /**
     * @return array
     */
    public function getGlobals(): array
    {
        return [
            'app_network_id' => $this->networkId,
            'app_name' => $this->appName,
            'app_google_analytics_id' => $this->googleAnalyticsId,
            'freshdesk_url' => $this->freshdeskUrl,
        ];
    }

    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('hash', [$this, 'hash']),
        ];
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_color', [$this, 'getColor']),
        ];
    }

    /**
     * Hashe le paramètre d'entrée pour en faire un identifiant unique.
     *
     * L'algorithme de hashage sélectionné est CRC32 pour sa rapidité.
     * Pas de besoin cryptographique ici.
     *
     * @param string $input
     *
     * @return string
     */
    public function hash(string $input): string
    {
        return (string) crc32($input);
    }

    /**
     * Détermine la couleur correspondant à une lettre,
     * pour le template Material Admin.
     *
     * @param string $letter
     *
     * @return string
     */
    public function getColor(string $letter): string
    {
        $letter = strtoupper($letter);

        if (!in_array($letter, range('A', 'Z'))) {
            return 'bg-black';
        }

        $colors = [
            'bg-black',
            'bg-pink',
            'bg-purple',
            'bg-deep-purple',
            'bg-indigo',
            'bg-blue',
            'bg-light-blue',
            'bg-cyan',
            'bg-teal',
            'bg-green',
            'bg-light-green',
            'bg-yellow',
            'bg-amber',
            'bg-orange',
            'bg-deep-orange',
            'bg-brown',
            'bg-blue-grey',
        ];

        $colorsCount = count($colors);
        $letterIndex = ord($letter) - ord('A');
        $colorIndex = $letterIndex % $colorsCount;

        return $colors[$colorIndex];
    }
}
