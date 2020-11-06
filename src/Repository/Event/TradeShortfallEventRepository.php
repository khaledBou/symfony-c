<?php

namespace App\Repository\Event;

use App\Entity\Event\TradeShortfallEvent;
use App\Entity\User\Mandatary;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * Repository des événements "plus de nouveau mandat".
 */
class TradeShortfallEventRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TradeShortfallEvent::class);
    }

    /**
     * Récupère l'événement "plus de nouveau mandat" survenu le jour $date.
     *
     * @param Mandatary          $mandatary
     * @param \DateTimeInterface $date
     *
     * @return TradeShortfallEvent|null
     */
    public function findOneByMandataryAtDate(Mandatary $mandatary, \DateTimeInterface $date): ?TradeShortfallEvent
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
