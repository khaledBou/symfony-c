<?php

namespace App\Repository\Event;

use App\Entity\Event\AdministrativeUpdateEvent;
use App\Entity\User\Mandatary;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * Repository des changements administratifs.
 */
class AdministrativeUpdateEventRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdministrativeUpdateEvent::class);
    }

    /**
     * Récupère l'événement de changement administratif le plus récent.
     *
     * @param Mandatary $mandatary
     *
     * @return AdministrativeUpdateEvent|null
     */
    public function findLatest(Mandatary $mandatary): ?AdministrativeUpdateEvent
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
