<?php

namespace App\Service\Calendar;

use App\Entity\Event\AppointmentEvent;
use App\Entity\User\ImportableUserInterface;

/**
 * Décrit comment doivent se comporter les helpers de gestion des agendas.
 */
interface CalendarHelperInterface
{
    /**
     * Crée un rendez-vous dans l'agenda d'un utilisateur.
     *
     * @param AppointmentEvent        $event
     * @param ImportableUserInterface $user
     *
     * @return bool
     */
    public function pushToCalendar(AppointmentEvent $event, ImportableUserInterface $user): bool;

    /**
     * Supprime un rendez-vous de l'agenda d'un utilisateur.
     *
     * @param AppointmentEvent        $event
     * @param ImportableUserInterface $user
     *
     * @return bool
     */
    public function removeFromCalendar(AppointmentEvent $event, ImportableUserInterface $user): bool;
}
