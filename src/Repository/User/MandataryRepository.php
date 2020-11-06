<?php

namespace App\Repository\User;

use App\Entity\User\Mandatary;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * Repository des négociateurs.
 */
class MandataryRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Mandatary::class);
    }

    /**
     * Récupère les animateurs.
     *
     * @param string $network Réseau
     *
     * @return Mandatary[]
     */
    public function findAnimators($network): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.animatedMandataries is not empty')
            ->andWhere('m.network = :network')
            ->orderBy('m.lastName, m.firstName', 'asc')
            ->setParameter('network', $network)
            ->getQuery()
            ->getResult()
        ;
    }
}
