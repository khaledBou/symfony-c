<?php

namespace App\Repository\Event;

use App\Entity\Event\CallEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * Repository des appels téléphoniques.
 */
class CallEventRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CallEvent::class);
    }

    /**
     * Récupère les appels téléphoniques de tous les négociateurs actifs d'un réseau.
     *
     * @param string $network
     *
     * @return CallEvent[]
     */
    public function findAllByNetwork(string $network): array
    {
        return $this->createQueryBuilder('e')
            ->join('e.mandatary', 'm')
            ->andWhere('m.network = :network')
            ->andWhere('m.enabled = :enabled')
            ->setParameter('network', $network)
            ->setParameter('enabled', true)
            ->getQuery()
            ->getResult()
        ;
    }
}
