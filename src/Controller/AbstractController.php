<?php

namespace App\Controller;

use App\Service\NetworkHelper;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Contrôleur parent, fournit un traducteur.
 */
abstract class AbstractController extends \Symfony\Bundle\FrameworkBundle\Controller\AbstractController
{
    /**
     * @var NetworkHelper
     */
    private $networkHelper;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param NetworkHelper       $networkHelper
     * @param TranslatorInterface $translator
     */
    public function __construct(NetworkHelper $networkHelper, TranslatorInterface $translator)
    {
        $this->networkHelper = $networkHelper;
        $this->translator = $translator;
    }

    /**
     * @return NetworkHelper
     */
    protected function getNetworkHelper(): NetworkHelper
    {
        return $this->networkHelper;
    }

    /**
     * @return TranslatorInterface
     */
    protected function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }
}
