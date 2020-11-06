<?php

namespace App\Controller;

use App\Entity\Event\AbstractEvent;
use App\Service\EventHelper;
use App\Service\NetworkHelper;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Contrôleur pour les événements.
 */
class EventController extends AbstractController
{
    /**
     * @var EventHelper
     */
    private $eventHelper;

    /**
     * @param NetworkHelper       $networkHelper
     * @param TranslatorInterface $translator
     * @param EventHelper         $eventHelper
     */
    public function __construct(NetworkHelper $networkHelper, TranslatorInterface $translator, EventHelper $eventHelper)
    {
        parent::__construct($networkHelper, $translator);

        $this->eventHelper = $eventHelper;
    }

    /**
     * Suppression d'un événement.
     *
     * @Route("/evenement/{id}/supprimer", name="event_delete")
     *
     * @param AbstractEvent $event
     *
     * @return Response
     */
    public function delete(AbstractEvent $event): Response
    {
        $mandatary = $event->getMandatary();

        // Si l'événement peut être supprimé
        if ($event->isDeletable()) {
            /**
             * Hook pour les traitements qui vont au delà
             * de la simple suppression de l'entité.
             *
             * Ce traitement est fait avant l'appel à la méthode remove
             * car il se peut qu'il fasse un persist intermédiaire.
             */
            $this->eventHelper->deleteHook($event);

            $em = $this->getDoctrine()->getManager();
            $em->remove($event);
            $em->flush();

            $this->addFlash('success', $this->getTranslator()->trans("Événement supprimé."));
        } else {
            $this->addFlash('notice', $this->getTranslator()->trans("Cet événement ne peut pas être supprimé."));
        }

        return $this->redirectToRoute('mandatary_show', [
            'slug' => $mandatary->getSlug(),
        ]);
    }
}
