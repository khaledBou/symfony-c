<?php

namespace App\Repository\Event;

use App\Entity\Event\ActivitiesUpdateEvent;
use App\Entity\User\Mandatary;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * Repository des changements d'activité.
 */
class ActivitiesUpdateEventRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivitiesUpdateEvent::class);
    }

    /**
     * Récupère l'événement de changement d'activité le plus récent.
     *
     * @param Mandatary $mandatary
     *
     * @return ActivitiesUpdateEvent|null
     */
    public function findLatest(Mandatary $mandatary): ?ActivitiesUpdateEvent
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.mandatary = :mandatary')
            ->setParameter('mandatary', $mandatary)
            ->orderBy('e.date', 'desc')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
