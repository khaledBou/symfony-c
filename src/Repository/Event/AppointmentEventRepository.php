<?php

namespace App\Repository\Event;

use App\Entity\Event\AppointmentEvent;
use App\Entity\User\Mandatary;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * Repository des rendez-vous.
 */
class AppointmentEventRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AppointmentEvent::class);
    }

    /**
     * Récupère le dernier rendez-vous passé (le plus récent),
     * pour un négociateur donné.
     *
     * @param Mandatary $mandatary
     *
     * @return AppointmentEvent|null
     */
    public function findLatest(Mandatary $mandatary): ?AppointmentEvent
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.mandatary = :mandatary')
            ->andWhere('e.date <= :now')
            ->setParameter('mandatary', $mandatary)
            ->setParameter('now', new \DateTime())
            ->orderBy('e.date', 'desc')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * Récupère le prochain rendez-vous prévu,
     * pour un négociateur donné.
     *
     * @param Mandatary $mandatary
     *
     * @return AppointmentEvent|null
     */
    public function findNext(Mandatary $mandatary): ?AppointmentEvent
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.mandatary = :mandatary')
            ->andWhere('e.date >= :now')
            ->setParameter('mandatary', $mandatary)
            ->setParameter('now', new \DateTime())
            ->orderBy('e.date', 'asc')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * Récupère les rendez-vous prévus le lendemain,
     * et qui n'ont pas déjà fait l'objet d'un SMS de rappel.
     *
     * @return AppointmentEvent[]
     */
    public function findToComeUpWithoutReminderSms(): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.date >= :now')
            ->andWhere('e.date <= :date')
            ->andWhere('e.reminderSmsSent = :reminder_sms_sent')
            ->setParameter('now', new \DateTime())
            ->setParameter('date', new \DateTime('+1 day 23:59:59'))
            ->setParameter('reminder_sms_sent', false)
            ->orderBy('e.date', 'asc')
            ->getQuery()
            ->getResult()
        ;
    }
}
