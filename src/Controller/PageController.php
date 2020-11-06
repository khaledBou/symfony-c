<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Contrôleur pour les pages de contenu.
 */
class PageController extends AbstractController
{
    /**
     * Guide utilisateur.
     *
     * @Route("/guide-utilisateur", name="page_doc")
     *
     * @return Response
     */
    public function doc(): Response
    {
        return $this->render(sprintf('page/doc.%s.html.twig', $this->getNetworkHelper()->getNetworkId()));
    }
}
