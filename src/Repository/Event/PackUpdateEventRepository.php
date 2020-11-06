<?php

namespace App\Repository\Event;

use App\Entity\Event\PackUpdateEvent;
use App\Entity\User\Mandatary;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * Repository des changements de pack.
 */
class PackUpdateEventRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PackUpdateEvent::class);
    }

    /**
     * Récupère l'événement de changement de pack le plus récent.
     *
     * @param Mandatary $mandatary
     *
     * @return PackUpdateEvent|null
     */
    public function findLatest(Mandatary $mandatary): ?PackUpdateEvent
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
