<?php

namespace App\Repository\User;

use App\Entity\User\Coach;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * Repository des coachs.
 */
class CoachRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Coach::class);
    }

    /**
     * Récupère les coachs actifs et qui ont des négociateurs.
     *
     * @param string $network Réseau
     *
     * @return Coach[]
     */
    public function findCoaches($network): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.enabled = true or (c.enabled = false and c.mandataries is not empty)')
            ->andWhere('c.network = :network')
            ->orderBy('c.lastName, c.firstName', 'asc')
            ->setParameter('network', $network)
            ->getQuery()
            ->getResult()
        ;
    }
}
