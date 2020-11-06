<?php

namespace App\Repository\Event;

use App\Entity\Event\CoachReminderEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * Repository des rappels.
 */
class CoachReminderEventRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CoachReminderEvent::class);
    }

    /**
     * Récupère les rappels à envoyer.
     *
     * @param int $way
     *
     * @return CoachReminderEvent[]
     */
    public function findToSend(int $way): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.sent = :sent')
            ->andWhere('e.date <= :now')
            ->andWhere('e.way = :way')
            ->setParameter('sent', false)
            ->setParameter('now', new \DateTime())
            ->setParameter('way', $way)
            ->getQuery()
            ->getResult()
        ;
    }
}
