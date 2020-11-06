<?php

namespace App\Repository\Event;

use App\Entity\Event\SaleShortfallEvent;
use App\Entity\User\Mandatary;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * Repository des événements "plus de nouvelle vente".
 */
class SaleShortfallEventRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SaleShortfallEvent::class);
    }

    /**
     * Récupère l'événement "plus de nouvelle vente" survenu le jour $date.
     *
     * @param Mandatary          $mandatary
     * @param \DateTimeInterface $date
     *
     * @return SaleShortfallEvent|null
     */
    public function findOneByMandataryAtDate(Mandatary $mandatary, \DateTimeInterface $date): ?SaleShortfallEvent
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
