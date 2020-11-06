<?php

namespace App\Repository\Event;

use App\Entity\Event\BeginningBirthdayEvent;
use App\Entity\User\Mandatary;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * Repository des anniversaires d'entrée dans le réseau.
 */
class BeginningBirthdayEventRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BeginningBirthdayEvent::class);
    }

    /**
     * Récupère l'événement d'anniversaire d'entrée dans le réseau,
     * pour les négociateurs entrés dans le réseau le jour $date.
     *
     * @param Mandatary          $mandatary
     * @param \DateTimeInterface $date
     *
     * @return BeginningBirthdayEvent|null
     */
    public function findOneByMandataryAtDate(Mandatary $mandatary, \DateTimeInterface $date): ?BeginningBirthdayEvent
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.mandatary = :mandatary')
            ->andWhere('e.date >= :begin_date')
            ->andWhere('e.date <= :end_date')
            ->setParameter('mandatary', $mandatary)
            ->setParameter('begin_date', (clone $date)->setTime(0, 0, 0))
            ->setParameter('end_date', (clone $date)->setTime(23, 59, 59))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
