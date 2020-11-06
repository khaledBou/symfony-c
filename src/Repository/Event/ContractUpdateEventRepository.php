<?php

namespace App\Repository\Event;

use App\Entity\Event\ContractUpdateEvent;
use App\Entity\User\Mandatary;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * Repository des changements de contrat.
 */
class ContractUpdateEventRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContractUpdateEvent::class);
    }

    /**
     * Récupère l'événement de changement de contrat le plus récent.
     *
     * @param Mandatary $mandatary
     *
     * @return ContractUpdateEvent|null
     */
    public function findLatest(Mandatary $mandatary): ?ContractUpdateEvent
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
